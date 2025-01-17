<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\ThemeFixtures;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class ThemeFixturesTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
    }

    public function testLoad(): void
    {
        $fixtures = new ThemeFixtures();
        
        // Utilisation de l'EntityManager réel
        $fixtures->load($this->entityManager);

        // Vérification que les données ont été chargées
        $themeRepository = $this->entityManager->getRepository('App:Theme');
        $themes = $themeRepository->findAll();
        
        $this->assertNotEmpty($themes, 'Les thèmes devraient être chargés');
        $this->assertCount(4, $themes, 'Il devrait y avoir 4 thèmes');
    }
} 