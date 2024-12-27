<?php

namespace App\Service;

use App\Entity\Cursus;
use App\Entity\Lesson;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeService
{
    public function __construct(
        private string $stripeSecretKey,
        private string $successUrl,
        private string $cancelUrl
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    public function createCursusCheckoutSession(Cursus $cursus): Session
    {
        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $cursus->getTitle(),
                    ],
                    'unit_amount' => $cursus->getPrice() * 100, // Conversion en centimes
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->cancelUrl,
            'metadata' => [
                'type' => 'cursus',
                'cursus_id' => $cursus->getId(),
            ],
        ]);
    }

    public function createLessonCheckoutSession(Lesson $lesson): Session
    {
        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $lesson->getTitle(),
                    ],
                    'unit_amount' => $lesson->getPrice() * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->cancelUrl,
            'metadata' => [
                'type' => 'lesson',
                'lesson_id' => $lesson->getId(),
            ],
        ]);
    }
}