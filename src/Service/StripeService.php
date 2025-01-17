<?php

namespace App\Service;

use App\Entity\Purchase;
use App\Entity\Cursus;
use App\Entity\Lesson;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service de gestion des paiements via Stripe
 * 
 * Ce service gère :
 * - La création des sessions de paiement Stripe
 * - Le traitement des webhooks Stripe
 * - La validation des paiements
 * - Le suivi des transactions
 */
class StripeService
{
    private string $webhookSecret;
    
    /**
     * Constructeur du service
     * 
     * @param string $stripeSecretKey Clé secrète Stripe
     * @param string $stripePublicKey Clé publique Stripe
     * @param string $webhookSecret Clé secrète pour les webhooks
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités
     * @param UrlGeneratorInterface $urlGenerator Générateur d'URLs
     * @param LoggerInterface $logger Service de journalisation
     */
    public function __construct(
        private string $stripeSecretKey,
        private string $stripePublicKey,
        string $webhookSecret,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger
    ) {
        Stripe::setApiKey($stripeSecretKey);
        $this->webhookSecret = $webhookSecret;
    }

    /**
     * Récupère la clé publique Stripe
     */
    public function getPublicKey(): string
    {
        return $this->stripePublicKey;
    }

    /**
     * Crée une session de paiement Stripe
     * 
     * @param string $name Nom du produit
     * @param string $description Description du produit
     * @param float $price Prix en euros
     * @param User $user Utilisateur effectuant l'achat
     * @param array $metadata Métadonnées additionnelles
     * @return Session Session de paiement Stripe
     * @throws \Exception Si la création de la session échoue
     */
    private function createCheckoutSession(string $name, string $description, float $price, User $user, array $metadata): Session
    {
        try {
            // Nettoyer et limiter la description
            $description = html_entity_decode(strip_tags($description));
            $description = substr($description, 0, 150) . '...';

            return Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $name,
                            'description' => $description,
                        ],
                        'unit_amount' => (int) ($price * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->urlGenerator->generate('app_purchase_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->urlGenerator->generate('app_purchase_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'customer_email' => $user->getEmail(),
                'metadata' => $metadata
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur création session', [
                'error' => $e->getMessage(),
                'metadata' => $metadata
            ]);
            throw $e;
        }
    }

    /**
     * Crée une session de paiement pour un cursus
     * 
     * @param Cursus $cursus Cursus à acheter
     * @param User $user Utilisateur effectuant l'achat
     * @return Session Session de paiement Stripe
     */
    public function createCursusCheckoutSession(Cursus $cursus, User $user): Session
    {
        $session = $this->createCheckoutSession(
            $cursus->getTitle(),
            $cursus->getDescription(),
            $cursus->getPrice(),
            $user,
            [
                'cursus_id' => $cursus->getId(),
                'user_id' => $user->getId(),
                'type' => 'cursus'
            ]
        );

        $this->logger->info('Session de paiement cursus créée', [
            'sessionId' => $session->id,
            'cursusId' => $cursus->getId(),
            'userId' => $user->getId()
        ]);

        return $session;
    }

    /**
     * Crée une session de paiement pour une leçon
     * 
     * @param Lesson $lesson Leçon à acheter
     * @param User $user Utilisateur effectuant l'achat
     * @return Session Session de paiement Stripe
     */
    public function createLessonCheckoutSession(Lesson $lesson, User $user): Session
    {
        $session = $this->createCheckoutSession(
            $lesson->getTitle(),
            $lesson->getContent(),
            $lesson->getPrice(),
            $user,
            [
                'lesson_id' => $lesson->getId(),
                'user_id' => $user->getId(),
                'type' => 'lesson'
            ]
        );

        $this->logger->info('Session de paiement leçon créée', [
            'sessionId' => $session->id,
            'lessonId' => $lesson->getId(),
            'userId' => $user->getId()
        ]);

        return $session;
    }

    /**
     * Met à jour le statut d'un achat
     * 
     * @param Session $session Session Stripe
     * @param string $status Nouveau statut
     */
    private function updatePurchaseStatus(Session $session, string $status): void
    {
        $purchase = $this->entityManager->getRepository(Purchase::class)
            ->findOneBy(['stripeSessionId' => $session->id]);

        if ($purchase) {
            $purchase->setStatus($status)
                    ->setUpdatedAt(new DateTimeImmutable());
            
            $this->entityManager->flush();
            
            $this->logger->info('Statut d\'achat mis à jour', [
                'purchaseId' => $purchase->getId(),
                'status' => $status
            ]);
        }
    }

    /**
     * Gère les webhooks Stripe
     * 
     * Traite les événements suivants :
     * - checkout.session.completed : Paiement réussi
     * - checkout.session.expired : Session expirée
     * 
     * @param Request $request Requête HTTP contenant l'événement Stripe
     * @return Response Réponse HTTP
     */
    public function handleWebhook(Request $request): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->headers->get('stripe-signature'),
                $this->webhookSecret
            );

            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->updatePurchaseStatus($event->data->object, 'completed');
                    break;
                case 'checkout.session.expired':
                    $this->updatePurchaseStatus($event->data->object, 'expired');
                    break;
            }

            return new Response('Webhook handled', Response::HTTP_OK);
        } catch (\UnexpectedValueException $e) {
            $this->logger->error('Webhook invalide', ['error' => $e->getMessage()]);
            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $this->logger->error('Signature webhook invalide', ['error' => $e->getMessage()]);
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error('Erreur webhook', ['error' => $e->getMessage()]);
            return new Response('Error processing webhook', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Valide un paiement via son ID de session
     * 
     * @param string $sessionId ID de la session Stripe
     * @return bool True si le paiement est validé
     */
    public function validatePurchase(string $sessionId): bool
    {
        try {
            $session = Session::retrieve($sessionId);
            return $session->payment_status === 'paid';
        } catch (\Exception $e) {
            $this->logger->error('Erreur validation paiement', [
                'sessionId' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}