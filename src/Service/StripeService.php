<?php

namespace App\Service;

use App\Entity\Purchase;
use App\Entity\Cursus;
use App\Entity\Lesson;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
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
        string $webhookSecret,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator
    ) {
        Stripe::setApiKey($stripeSecretKey);
        $this->webhookSecret = $webhookSecret;
    }

    public function createCursusCheckoutSession(Cursus $cursus, User $user): Session
    {
        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $cursus->getTitle(),
                        'description' => $cursus->getDescription(),
                    ],
                    'unit_amount' => (int) ($cursus->getPrice() * 100), // Conversion en centimes
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->urlGenerator->generate('app_purchase_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->urlGenerator->generate('app_purchase_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'customer_email' => $user->getEmail(),
            'metadata' => [
                'cursus_id' => $cursus->getId(),
                'user_id' => $user->getId(),
                'type' => 'cursus'
            ],
        ]);
    }

    public function createLessonCheckoutSession(Lesson $lesson, User $user): Session
    {
        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $lesson->getTitle(),
                        'description' => $lesson->getContent(),
                    ],
                    'unit_amount' => (int) ($lesson->getPrice() * 100), // Conversion en centimes
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->urlGenerator->generate('app_purchase_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->urlGenerator->generate('app_purchase_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'customer_email' => $user->getEmail(),
            'metadata' => [
                'lesson_id' => $lesson->getId(),
                'user_id' => $user->getId(),
                'type' => 'lesson'
            ],
        ]);
    }

    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->webhookSecret
            );

            switch ($event->type) {
                case 'checkout.session.completed':
                    $session = $event->data->object;
                    
                    // Trouver l'achat correspondant
                    $purchase = $this->entityManager->getRepository(Purchase::class)
                        ->findOneBy(['stripeSessionId' => $session->id]);

                    if ($purchase) {
                        $purchase->setStatus('completed')
                               ->setUpdatedAt(new DateTimeImmutable());
                        
                        $this->entityManager->flush();
                    }
                    break;

                case 'checkout.session.expired':
                    $session = $event->data->object;
                    
                    $purchase = $this->entityManager->getRepository(Purchase::class)
                        ->findOneBy(['stripeSessionId' => $session->id]);

                    if ($purchase) {
                        $purchase->setStatus('expired')
                               ->setUpdatedAt(new DateTimeImmutable());
                        
                        $this->entityManager->flush();
                    }
                    break;
            }

            return new Response('Webhook handled', Response::HTTP_OK);
        } catch (\UnexpectedValueException $e) {
            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new Response('Error processing webhook', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function validatePurchase(string $sessionId): bool
    {
        try {
            $session = Session::retrieve($sessionId);
            return $session->payment_status === 'paid';
        } catch (\Exception $e) {
            return false;
        }
    }
}