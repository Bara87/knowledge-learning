<?php

namespace App\Controller;

use App\Entity\Purchase;
use App\Entity\Cursus;
use App\Entity\Lesson;
use App\Service\StripeService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur de gestion des achats
 * 
 * Ce contrôleur gère toutes les opérations liées aux achats :
 * - Achat de cursus complets
 * - Achat de leçons individuelles
 * - Traitement des webhooks Stripe
 * - Gestion des succès/échecs de paiement
 */
#[Route('/purchase')]
class PurchaseController extends AbstractController
{
    /**
     * Constructeur avec injection des dépendances
     * 
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
     * @param StripeService $stripeService Service de gestion des paiements Stripe
     * @param LoggerInterface $logger Service de logging
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StripeService $stripeService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Gère l'achat d'un cursus
     * 
     * Processus :
     * 1. Vérifie si le cursus est payant
     * 2. Vérifie si l'utilisateur n'a pas déjà acheté ce cursus
     * 3. Crée une session de paiement Stripe
     * 4. Enregistre l'achat en statut 'pending'
     * 5. Redirige vers la page de paiement Stripe
     * 
     * @param Cursus $cursus Cursus à acheter
     * @throws AccessDeniedException Si le cursus est gratuit
     */
    #[Route('/cursus/{id}', name: 'app_purchase_cursus', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function purchaseCursus(Cursus $cursus): Response
    {
        // Vérifier si le cursus est payant
        if ($cursus->getPrice() <= 0) {
            throw $this->createAccessDeniedException('Ce cursus est gratuit.');
        }

        // Vérifier si l'utilisateur a déjà acheté ce cursus
        $existingPurchase = $this->entityManager->getRepository(Purchase::class)->findOneBy([
            'user' => $this->getUser(),
            'cursus' => $cursus,
            'status' => 'completed'
        ]);

        if ($existingPurchase) {
            $this->addFlash('warning', 'Vous avez déjà acheté ce cursus.');
            return $this->redirectToRoute('app_cursus_show', ['id' => $cursus->getId()]);
        }

        try {
            // Créer la session de paiement Stripe
            $session = $this->stripeService->createCursusCheckoutSession($cursus, $this->getUser());

            // Créer l'enregistrement de l'achat en attente
            $purchase = new Purchase();
            $purchase->setUser($this->getUser())
                    ->setCursus($cursus)
                    ->setAmount($cursus->getPrice())
                    ->setStatus('pending')
                    ->setStripeSessionId($session->payment_intent ?? $session->id)
                    ->setCreatedAt(new DateTimeImmutable());

            $this->entityManager->persist($purchase);
            $this->entityManager->flush();

            return $this->redirect($session->url);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la création du paiement.');
            return $this->redirectToRoute('app_cursus_show', ['id' => $cursus->getId()]);
        }
    }

    /**
     * Gère l'achat d'une leçon individuelle
     * 
     * Processus :
     * 1. Vérifie si la leçon est payante
     * 2. Vérifie si l'utilisateur n'a pas déjà acheté cette leçon
     * 3. Vérifie si la leçon ne fait pas partie d'un cursus déjà acheté
     * 4. Crée une session de paiement Stripe
     * 5. Enregistre l'achat en statut 'pending'
     * 
     * @param Lesson $lesson Leçon à acheter
     * @throws AccessDeniedException Si la leçon est gratuite
     */
    #[Route('/lesson/{id}', name: 'app_purchase_lesson', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function purchaseLesson(Lesson $lesson): Response
    {
        // Vérifier si la leçon est payante
        if ($lesson->getPrice() <= 0) {
            throw $this->createAccessDeniedException('Cette leçon est gratuite.');
        }

        // Vérifier si l'utilisateur a déjà acheté cette leçon
        $existingPurchase = $this->entityManager->getRepository(Purchase::class)->findOneBy([
            'user' => $this->getUser(),
            'lesson' => $lesson,
            'status' => 'completed'
        ]);

        if ($existingPurchase) {
            $this->addFlash('warning', 'Vous avez déjà acheté cette leçon.');
            return $this->redirectToRoute('app_lesson_show', ['id' => $lesson->getId()]);
        }

        // Si la leçon fait partie d'un cursus déjà acheté
        if ($lesson->getCursus()) {
            $cursusPurchase = $this->entityManager->getRepository(Purchase::class)->findOneBy([
                'user' => $this->getUser(),
                'cursus' => $lesson->getCursus(),
                'status' => 'completed'
            ]);

            if ($cursusPurchase) {
                $this->addFlash('info', 'Cette leçon fait partie d\'un cursus que vous avez déjà acheté.');
                return $this->redirectToRoute('app_lesson_show', ['id' => $lesson->getId()]);
            }
        }

        try {
            // Créer la session de paiement Stripe
            $session = $this->stripeService->createLessonCheckoutSession($lesson, $this->getUser());

            // Créer l'enregistrement de l'achat en attente
            $purchase = new Purchase();
            $purchase->setUser($this->getUser())
                    ->setLesson($lesson)
                    ->setAmount($lesson->getPrice())
                    ->setStatus('pending')
                    ->setStripeSessionId($session->id)
                    ->setCreatedAt(new DateTimeImmutable());

            $this->entityManager->persist($purchase);
            $this->entityManager->flush();

            return $this->redirect($session->url);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la création du paiement.');
            return $this->redirectToRoute('app_lesson_show', ['id' => $lesson->getId()]);
        }
    }

    /**
     * Traite les webhooks Stripe
     * 
     * Gère les événements de paiement :
     * - Vérifie l'authenticité du webhook
     * - Traite l'événement 'checkout.session.completed'
     * - Met à jour le statut de l'achat
     * - Enregistre les logs
     * 
     * @param Request $request Requête contenant les données du webhook
     */
    #[Route('/webhook', name: 'app_purchase_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        try {
            // Vérifie l'authenticité du webhook avec la signature Stripe
            $event = \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $request->headers->get('stripe-signature'),
                $_ENV['STRIPE_WEBHOOK_SECRET']
            );
            // Vérifie l'authenticité du webhook
            $this->logger->info('Webhook reçu', [
                'type' => $event->type,
                'data' => json_encode($event->data)
            ]);

            // Ne traiter que l'événement checkout.session.completed
            if ($event->type === 'checkout.session.completed') {
                $session = $event->data->object;

                // Rechercher l'achat uniquement par session ID
                $purchase = $this->entityManager->getRepository(Purchase::class)
                    ->findOneBy(['stripeSessionId' => $session->id]);

                if ($purchase) {
                    $purchase->setStatus('completed')
                            ->setUpdatedAt(new DateTimeImmutable());
                    
                    $this->entityManager->flush();

                    $this->logger->info('Achat mis à jour', [
                        'purchase_id' => $purchase->getId(),
                        'old_status' => $purchase->getStatus()
                    ]);
                } else {
                    $this->logger->error('Achat non trouvé', [
                        'session_id' => $session->id
                    ]);
                }
            }

            return new Response('Webhook handled', Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error('Erreur webhook', [
                'error' => $e->getMessage()
            ]);
            return new Response('Error: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Traite un payment intent Stripe
     * 
     * Méthode privée utilisée pour :
     * - Rechercher l'achat correspondant
     * - Mettre à jour son statut si le paiement est réussi
     * - Logger les informations
     * 
     * @param mixed $paymentIntent Objet payment intent de Stripe
     */
    private function handlePaymentIntent($paymentIntent): void
    {
        $this->logger->info('Traitement payment intent', [
            'id' => $paymentIntent->id,
            'status' => $paymentIntent->status
        ]);

        $purchase = $this->entityManager->getRepository(Purchase::class)
            ->findOneBy(['stripeSessionId' => $paymentIntent->id]);

        if ($purchase && $paymentIntent->status === 'succeeded') {
            $purchase->setStatus('completed')
                    ->setUpdatedAt(new DateTimeImmutable());
            
            $this->entityManager->flush();

            $this->logger->info('Purchase mis à jour via payment intent', [
                'id' => $purchase->getId(),
                'new_status' => $purchase->getStatus()
            ]);
        }
    }

    /**
     * Page de succès après paiement
     * 
     * Affiche un message de confirmation et redirige vers le profil
     */
    #[Route('/success', name: 'app_purchase_success')]
    #[IsGranted('ROLE_USER')]
    public function success(): Response
    {
        $this->addFlash('success', 'Votre achat a été effectué avec succès !');
        return $this->redirectToRoute('app_profile');
    }

    /**
     * Page d'annulation de paiement
     * 
     * Affiche un message d'erreur et redirige vers l'accueil
     */
    #[Route('/cancel', name: 'app_purchase_cancel')]
    #[IsGranted('ROLE_USER')]
    public function cancel(): Response
    {
        $this->addFlash('error', 'L\'achat a été annulé.');
        return $this->redirectToRoute('app_home');
    }

    /**
     * Traite une session de checkout Stripe
     * 
     * Méthode privée utilisée pour :
     * - Rechercher l'achat par session ID ou payment intent
     * - Mettre à jour son statut selon le statut du paiement
     * - Logger les informations
     * 
     * @param mixed $session Objet session de Stripe
     */
    private function handleCheckoutSession($session): void
    {
        $this->logger->info('Traitement session checkout', [
            'session_id' => $session->id,
            'payment_intent' => $session->payment_intent,
            'payment_status' => $session->payment_status
        ]);

        $purchase = $this->entityManager->getRepository(Purchase::class)
            ->findOneBy(['stripeSessionId' => $session->id]);

        if (!$purchase) {
            $purchase = $this->entityManager->getRepository(Purchase::class)
                ->findOneBy(['stripeSessionId' => $session->payment_intent]);
        }

        if ($purchase) {
            $this->logger->info('Purchase trouvé', [
                'id' => $purchase->getId(),
                'old_status' => $purchase->getStatus()
            ]);

            if ($session->payment_status === 'paid') {
                $purchase->setStatus(Purchase::STATUS_COMPLETED)
                        ->setUpdatedAt(new DateTimeImmutable());
                
                $this->entityManager->flush();

                $this->logger->info('Purchase mis à jour', [
                    'id' => $purchase->getId(),
                    'new_status' => $purchase->getStatus()
                ]);
            } elseif ($session->payment_status === 'failed') {
                $purchase->setStatus(Purchase::STATUS_FAILED)
                        ->setUpdatedAt(new DateTimeImmutable());
                
                $this->entityManager->flush();
            }
        }
    }
}