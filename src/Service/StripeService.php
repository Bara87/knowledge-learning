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

class StripeService
{
    private string $webhookSecret;
    
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

    public function getPublicKey(): string
    {
        return $this->stripePublicKey;
    }

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