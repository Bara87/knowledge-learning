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

#[Route('/purchase')]
class PurchaseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StripeService $stripeService
    ) {
    }

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
                    ->setStripeSessionId($session->id)
                    ->setCreatedAt(new DateTimeImmutable());

            $this->entityManager->persist($purchase);
            $this->entityManager->flush();

            return $this->redirect($session->url);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la création du paiement.');
            return $this->redirectToRoute('app_cursus_show', ['id' => $cursus->getId()]);
        }
    }

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

    #[Route('/webhook', name: 'app_purchase_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        return $this->stripeService->handleWebhook($request);
    }

    #[Route('/success', name: 'app_purchase_success')]
    #[IsGranted('ROLE_USER')]
    public function success(): Response
    {
        $this->addFlash('success', 'Votre achat a été effectué avec succès !');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/cancel', name: 'app_purchase_cancel')]
    #[IsGranted('ROLE_USER')]
    public function cancel(): Response
    {
        $this->addFlash('error', 'L\'achat a été annulé.');
        return $this->redirectToRoute('app_home');
    }

    
}