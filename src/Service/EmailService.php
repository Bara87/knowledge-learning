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
        $email = (new Email())
            ->from($this->adminEmail)
            ->to($user->getEmail())
            ->subject('Activation de votre compte')
            ->html($this->twig->render('service/activation.html.twig', [
                'user' => $user,
                'activation_url' => $activationUrl
            ]));

        $this->mailer->send($email);
    }
}