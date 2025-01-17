<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;

/**
 * Service de gestion des envois d'emails
 * 
 * Ce service gère :
 * - L'envoi d'emails d'activation de compte
 * - L'envoi d'emails de réinitialisation de mot de passe
 * - La validation des adresses email
 * - La journalisation des envois et des erreurs
 */
class EmailService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private string $adminEmail;
    private LoggerInterface $logger;
    private ValidatorInterface $validator;

    /**
     * Constructeur du service
     * 
     * @param MailerInterface $mailer Service d'envoi d'emails Symfony
     * @param Environment $twig Moteur de template Twig
     * @param string $adminEmail Adresse email de l'administrateur
     * @param LoggerInterface $logger Service de journalisation
     * @param ValidatorInterface $validator Service de validation Symfony
     */
    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        string $adminEmail,
        LoggerInterface $logger,
        ValidatorInterface $validator
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->adminEmail = $adminEmail;
        $this->logger = $logger;
        $this->validator = $validator;
    }

    /**
     * Envoie un email d'activation de compte
     * 
     * @param User $user Utilisateur destinataire
     * @param string $activationUrl URL d'activation du compte
     * @throws \RuntimeException Si l'envoi échoue ou si l'email est invalide
     */
    public function sendActivationEmail(User $user, string $activationUrl): void
    {
        $this->validateEmail($user->getEmail());

        try {
            $email = (new Email())
                ->from($this->adminEmail)
                ->to($user->getEmail())
                ->subject('Activation de votre compte')
                ->html($this->twig->render('service/activation.html.twig', [
                    'user' => $user,
                    'activation_url' => $activationUrl,
                ]));

            $this->mailer->send($email);
            $this->logger->info('Email d\'activation envoyé avec succès', ['email' => $user->getEmail()]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email d\'activation', [
                'exception' => $e,
                'user_email' => $user->getEmail(),
            ]);
            throw new \RuntimeException('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
        }
    }

    /**
     * Envoie un email de réinitialisation de mot de passe
     * 
     * @param User $user Utilisateur destinataire
     * @param string $token Token de réinitialisation
     * @throws \RuntimeException Si l'envoi échoue ou si l'email est invalide
     */
    public function sendPasswordResetEmail(User $user, string $token): void
    {
        $this->logger->debug('Début sendPasswordResetEmail', [
            'user_email' => $user->getEmail(),
            'admin_email' => $this->adminEmail,
            'token' => $token
        ]);

        $this->validateEmail($user->getEmail());

        try {
            $this->logger->debug('Construction de l\'email');
            
            $email = (new Email())
                ->from($this->adminEmail)
                ->to($user->getEmail())
                ->subject('Réinitialisation de mot de passe')
                ->html($this->twig->render('service/reset_password.html.twig', [
                    'token' => $token,
                    'user' => $user,
                ]));

            $this->logger->debug('Tentative d\'envoi de l\'email', [
                'from' => $this->adminEmail,
                'to' => $user->getEmail(),
                'subject' => 'Réinitialisation de mot de passe'
            ]);

            $this->mailer->send($email);
            
            $this->logger->info('Email envoyé avec succès', [
                'from' => $this->adminEmail,
                'to' => $user->getEmail()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur détaillée lors de l\'envoi:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'from_email' => $this->adminEmail
            ]);
            throw new \RuntimeException('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
        }
    }

    /**
     * Valide une adresse email
     * 
     * @param string $email Adresse email à valider
     * @throws \RuntimeException Si l'adresse email est invalide
     */
    private function validateEmail(string $email): void
    {
        $violations = $this->validator->validate($email, [new Assert\Email()]);
        if (count($violations) > 0) {
            $this->logger->warning('Adresse email invalide détectée', ['email' => $email]);
            throw new \RuntimeException('Adresse email invalide : ' . $email);
        }
    }
}
