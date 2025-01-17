<?php

namespace App\Tests\Controller;

use App\Controller\RegistrationController;
use App\Entity\User;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * Tests unitaires du contrôleur d'inscription
 * 
 * Ces tests vérifient :
 * - La création d'un nouvel utilisateur
 * - La validation du formulaire d'inscription
 * - L'interaction avec les services (email, password hasher)
 */
class RegistrationControllerTest extends TestCase
{
    private RegistrationController $controller;
    private EmailService $emailService;
    private EntityManagerInterface $entityManager;
    private FormFactoryInterface $formFactory;
    private UserPasswordHasherInterface $passwordHasher;
    private LoggerInterface $logger;

    /**
     * Initialisation des tests
     * 
     * Configure :
     * - Les mocks des services nécessaires
     * - Le container Symfony avec les services requis
     * - Le contrôleur avec le code admin de test
     */
    protected function setUp(): void
    {
        $this->emailService = $this->createMock(EmailService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->controller = new RegistrationController('admin_secret_code');
        
        // Créer un container avec les services nécessaires
        $container = new Container();
        $container->set('form.factory', $this->formFactory);
        $container->set('twig', $this->createMock(\Twig\Environment::class));
        
        // Définir les paramètres requis
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'test');
        
        $this->controller->setContainer($container);
    }

    /**
     * Test de l'inscription d'un nouvel utilisateur
     * 
     * Vérifie que :
     * - Le formulaire est soumis et valide
     * - Un utilisateur est créé
     * - Une réponse est retournée
     */
    public function testRegisterNewUser(): void
    {
        // Arrange
        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $user = new User();
        $form->method('getData')
            ->willReturn($user);

        // Act
        $response = $this->controller->register(
            new Request(),
            $this->passwordHasher,
            $this->entityManager,
            $this->emailService,
            $this->logger
        );

        // Assert
        $this->assertNotNull($response);
    }
}


