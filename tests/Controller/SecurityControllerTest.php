<?php

namespace App\Tests\Controller;

use App\Controller\SecurityController;
use App\Entity\User;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Psr\Log\LoggerInterface;

class SecurityControllerTest extends WebTestCase
{
    public function testForgotPassword()
    {
        // Créer un client de test Symfony
        $client = static::createClient();

        // Créer un utilisateur fictif pour tester
        $user = new User();
        $user->setEmail('user@example.com');

        // Créer un mock pour EntityManagerInterface
        $entityManager = $this->createMock(EntityManagerInterface::class);

        // Créer un mock de repository pour simuler la recherche de l'utilisateur
        $mockRepository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $mockRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'user@example.com'])
            ->willReturn($user);

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($mockRepository);

        // Créer des mocks pour les autres services nécessaires
        $emailService = $this->createMock(EmailService::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        // Simuler le comportement du TokenGenerator
        $tokenGenerator->expects($this->once())
            ->method('generateToken')
            ->willReturn('validToken');

        // Créer un container avec les mocks nécessaires
        $container = $client->getContainer();
        $container->set(EntityManagerInterface::class, $entityManager);
        $container->set(EmailService::class, $emailService);
        $container->set(TokenGeneratorInterface::class, $tokenGenerator);
        $container->set(LoggerInterface::class, $logger);

        // Effectuer une requête POST pour simuler le formulaire de mot de passe oublié
        $crawler = $client->request('POST', '/forgot-password', [
            'email' => 'user@example.com',
        ]);

        // Vérifier que la réponse est bien une redirection (ou autre comportement attendu)
        $this->assertResponseRedirects('/some-path-or-page'); // Modifier en fonction du comportement attendu

        // Vérifier que l'email a été envoyé
        $emailService->expects($this->once())
            ->method('sendPasswordResetEmail')
            ->with($user, 'validToken');
    }
}
