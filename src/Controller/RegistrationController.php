<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Psr\Log\LoggerInterface;
use App\Service\EmailService;

/**
 * Contrôleur gérant l'inscription des utilisateurs
 * 
 * Ce contrôleur gère :
 * - L'inscription des nouveaux utilisateurs
 * - La vérification des emails
 * - L'attribution des rôles (ROLE_USER ou ROLE_ADMIN)
 */
class RegistrationController extends AbstractController
{
    /**
     * Constructeur du contrôleur
     * 
     * @param string $adminCode Code secret pour l'inscription en tant qu'administrateur
     */
    public function __construct(
        private string $adminCode
    ) {}

    /**
     * Gère le processus d'inscription d'un nouvel utilisateur
     * 
     * Cette méthode :
     * - Crée un nouvel utilisateur
     * - Vérifie si le code admin est correct
     * - Hash le mot de passe
     * - Génère un token d'activation
     * - Envoie un email de confirmation
     * 
     * @param Request $request Requête HTTP
     * @param UserPasswordHasherInterface $userPasswordHasher Service de hashage des mots de passe
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
     * @param EmailService $emailService Service d'envoi d'emails
     * @param LoggerInterface $logger Service de journalisation
     * @return Response Vue du formulaire ou redirection
     */
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        EmailService $emailService,
        LoggerInterface $logger
    ): Response {
        $logger->info('Début du processus d\'inscription');
        
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logger->info('Formulaire soumis et valide');
            
            try {
                // Vérifier le code admin
                $adminCode = $form->get('adminCode')->getData();
                $logger->info('Code admin vérifié', ['isAdmin' => ($adminCode === $this->adminCode)]);

                if ($adminCode === $this->adminCode) {
                    $user->setRoles(['ROLE_ADMIN']);
                } else {
                    $user->setRoles(['ROLE_USER']);
                }

                // Hasher le mot de passe
                $user->setPassword($userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
                $logger->info('Mot de passe hashé');

                // Générer le token d'activation
                $user->setActivationToken(bin2hex(random_bytes(32)));
                $user->setIsVerified(false);
                $user->setIsActive(false);
                $logger->info('Token généré et statuts initialisés');

                $entityManager->persist($user);
                $entityManager->flush();
                $logger->info('Utilisateur persisté en base de données');

                $activationUrl = $this->generateUrl('app_verify_email', [
                    'token' => $user->getActivationToken()
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $logger->info('Tentative d\'envoi d\'email', [
                    'to' => $user->getEmail(),
                    'activation_url' => $activationUrl
                ]);

                // Envoi de l'email
                $emailService->sendActivationEmail($user, $activationUrl);
                $logger->info('Email envoyé avec succès');

                $this->addFlash('success', 'Votre compte a été créé. Veuillez vérifier vos emails pour l\'activer.');
                return $this->redirectToRoute('app_login');

            } catch (\Exception $e) {
                $logger->error('Erreur pendant l\'inscription', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription : ' . $e->getMessage());
                return $this->redirectToRoute('app_register');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    /**
     * Vérifie l'email d'un utilisateur via le token d'activation
     * 
     * Cette méthode :
     * - Recherche l'utilisateur par son token
     * - Active le compte
     * - Supprime le token d'activation
     * 
     * @param string $token Token d'activation
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
     * @return Response Redirection vers la page d'inscription
     * @throws NotFoundHttpException Si aucun utilisateur n'est trouvé avec ce token
     */
    #[Route('/verify/email/{token}', name: 'app_verify_email')]
    public function verifyUserEmail(
        string $token,
        EntityManagerInterface $entityManager
    ): Response {
        // Chercher l'utilisateur par le token
        $user = $entityManager->getRepository(User::class)->findOneBy([
            'activationToken' => $token
        ]);

        // Vérifier si un utilisateur a été trouvé
        if (!$user) {
            throw $this->createNotFoundException('Aucun utilisateur trouvé avec ce token d\'activation.');
        }

        // Activer le compte
        $user->setIsVerified(true);
        $user->setIsActive(true);
        $user->setActivationToken(null); // Effacer le token après utilisation

        $entityManager->flush();

        $this->addFlash('success', 'Votre compte a été activé avec succès !');

        return $this->redirectToRoute('app_register');
    }
}