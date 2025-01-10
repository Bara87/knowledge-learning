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

class RegistrationController extends AbstractController
{
    public function __construct(
        private string $fromEmail
    ) {}

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $authenticator,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            
            // Générer et définir le token d'activation
            $user->setActivationToken(bin2hex(random_bytes(32)));
            
            // Définir les états initiaux de l'utilisateur
            $user->setIsVerified(false);
            $user->setIsActive(false);

            $entityManager->persist($user);
            $entityManager->flush();

            // Génération de l'URL d'activation
            $activationUrl = $this->generateUrl('app_verify_email', [
                'token' => $user->getActivationToken()
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            // Création et envoi de l'email
            $email = (new Email())
                ->from($this->fromEmail)
                ->to($user->getEmail())
                ->subject('Activation de votre compte')
                ->html("<p>Cliquez sur ce lien pour activer votre compte : <a href='{$activationUrl}'>Activer mon compte</a></p>");

            $mailer->send($email);

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

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