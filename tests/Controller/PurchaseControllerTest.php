<?php

namespace App\Tests\Controller;

use App\Controller\PurchaseController;
use App\Entity\Cursus;
use App\Entity\Purchase;
use App\Entity\User;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests unitaires du contrôleur d'achats
 * 
 * Ces tests vérifient :
 * - Le processus d'achat de cursus
 * - La gestion des achats en double
 * - Les interactions avec Stripe
 * - La persistance des achats
 */
class PurchaseControllerTest extends KernelTestCase
{
    private $entityManager;
    private $stripeService;
    private $logger;
    private $controller;

    /**
     * Initialisation des tests
     * 
     * Configure :
     * - Le kernel Symfony
     * - Les mocks des services (EntityManager, StripeService, Logger)
     * - La session et le FlashBag
     * - Le contrôleur avec ses dépendances
     */
    protected function setUp(): void
    {
        self::bootKernel();
        
        // Créer un mock de FlashBag
        $flashBag = $this->createMock(\Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface::class);
        
        // Créer un mock de Session qui implémente FlashBagAwareSessionInterface
        $session = $this->createMock(\Symfony\Component\HttpFoundation\Session\Session::class);
        $session->method('getFlashBag')->willReturn($flashBag);
        
        // Configurer la request stack
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push(new Request());
        $requestStack->getCurrentRequest()->setSession($session);
        
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->stripeService = $this->getMockBuilder(StripeService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->createMock(LoggerInterface::class);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->entityManager;
        /** @var StripeService $stripeService */
        $stripeService = $this->stripeService;
        /** @var LoggerInterface $logger */
        $logger = $this->logger;

        $this->controller = new PurchaseController($entityManager, $stripeService, $logger);
        $this->controller->setContainer(self::getContainer());
    }

    /**
     * Test de la redirection lors d'un achat en double
     * 
     * Vérifie que :
     * - Un cursus déjà acheté déclenche une redirection
     * - Le code de statut est 302 (redirection)
     * - L'achat existant est correctement détecté
     */
    public function testPurchaseCursusRedirectsWhenAlreadyPurchased(): void
    {
        // Arrange
        $cursus = new Cursus();
        $cursus->setPrice(100);
        
        // Définir l'ID via réflexion
        $reflection = new \ReflectionClass($cursus);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($cursus, 1);

        $user = new User();
        $purchase = new Purchase();
        $purchase->setStatus('completed');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($purchase);

        $this->entityManager
            ->method('getRepository')
            ->willReturn($repository);

        // Act
        $response = $this->controller->purchaseCursus($cursus);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
    }
} 