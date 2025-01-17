<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;
use App\Form\ResetPasswordRequestType;
use App\Form\ResetPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use App\Service\EmailService;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur gérant la sécurité et l'authentification
 * 
 * Ce contrôleur gère :
 * - La connexion des utilisateurs
 * - La déconnexion
 * - La réinitialisation de mot de passe
 * - La gestion des tokens de réinitialisation
 */
class SecurityController extends AbstractController
{
    /**
     * Gère la page de connexion
     * 
     * @param AuthenticationUtils $authenticationUtils Utilitaire d'authentification Symfony
     * @return Response Page de connexion ou redirection
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error
        ]);
    }

    /**
     * Gère la déconnexion
     * 
     * Cette méthode est interceptée par le firewall
     * 
     * @throws \LogicException Toujours levée car la méthode ne devrait jamais être appelée directement
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Gère la demande de réinitialisation de mot de passe
     * 
     * Cette méthode :
     * - Génère un token de réinitialisation
     * - Envoie un email avec le lien de réinitialisation
     * - Enregistre le token dans la base de données
     * 
     * @param Request $request Requête HTTP
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités
     * @param EmailService $emailService Service d'envoi d'emails
     * @param TokenGeneratorInterface $tokenGenerator Générateur de tokens
     * @param LoggerInterface $logger Service de journalisation
     * @return Response Vue du formulaire ou redirection
     */
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        EmailService $emailService,
        TokenGeneratorInterface $tokenGenerator,
        LoggerInterface $logger
    ): Response {
        try {
            $form = $this->createForm(ResetPasswordRequestType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $email = $form->get('email')->getData();
                $logger->info('Tentative de réinitialisation pour email: ' . $email);
                
                $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                if ($user) {
                    $token = $tokenGenerator->generateToken();
                    $user->setResetToken($token);
                    $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
                    $entityManager->flush();
                    
                    $logger->info('Token généré pour utilisateur: ' . $user->getId());

                    try {
                        $emailService->sendPasswordResetEmail($user, $token);
                        $logger->info('Email envoyé avec succès');
                    } catch (\Exception $e) {
                        $logger->error('Erreur lors de l\'envoi: ' . $e->getMessage());
                        throw $e;
                    }
                }

                $this->addFlash('success', 'Un email vous a été envoyé pour réinitialiser votre mot de passe.');
                return $this->redirectToRoute('app_login');
            }
        } catch (\Exception $e) {
            $logger->error('Erreur générale: ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue. Veuillez réessayer.');
        }

        return $this->render('security/forgot_password.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Gère la réinitialisation effective du mot de passe
     * 
     * Cette méthode :
     * - Vérifie la validité du token
     * - Permet à l'utilisateur de définir un nouveau mot de passe
     * - Met à jour le mot de passe en base de données
     * 
     * @param string $token Token de réinitialisation
     * @param Request $request Requête HTTP
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités
     * @param UserPasswordHasherInterface $passwordHasher Service de hashage des mots de passe
     * @return Response Vue du formulaire ou redirection
     */
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'Token invalide ou expiré');
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hasher le nouveau mot de passe
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('password')->getData()
            );

            $user->setPassword($hashedPassword);
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été mis à jour.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView()
        ]);
    }
}