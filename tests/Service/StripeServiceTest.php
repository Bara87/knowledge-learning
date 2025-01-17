<?php
namespace App\Tests\Service;

use App\Entity\Cursus;
use App\Entity\User;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeServiceTest extends TestCase
{
    private $entityManager;
    private $urlGenerator;
    private $logger;
    private $stripeService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->stripeService = new StripeService(
            'sk_test_key',
            'pk_test_key',
            'whsec_test',
            $this->entityManager,
            $this->urlGenerator,
            $this->logger
        );
    }

    public function testCreateCursusCheckoutSession(): void
    {
        // Arrange
        $cursus = new Cursus();
        $cursus->setTitle('Test Cursus');
        $cursus->setPrice(99.99);
        
        $user = new User();
        $user->setEmail('test@example.com');

        // Act & Assert
        $this->expectException(\Stripe\Exception\InvalidRequestException::class);
        $this->stripeService->createCursusCheckoutSession($cursus, $user);
    }
} 