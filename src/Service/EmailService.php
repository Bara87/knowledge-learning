<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $adminEmail
    ) {}

    public function sendActivationEmail(User $user, string $activationUrl): void
    {
        try {
            $email = (new Email())
                ->from($this->adminEmail)
                ->to($user->getEmail())
                ->subject('Activation de votre compte')
                ->html($this->twig->render('service/activation.html.twig', [
                    'user' => $user,
                    'activation_url' => $activationUrl
                ]));

            $this->mailer->send($email);
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
        }
    }
}