<?php

namespace App\Tests\Repository;

use App\Entity\Lesson;
use App\Entity\User;
use App\Entity\Cursus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LessonRepositoryTest extends KernelTestCase
{
    private $entityManager;
    private $lessonRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->lessonRepository = $this->entityManager->getRepository(Lesson::class);
    }

    public function testFindByCursusWithValidations(): void
    {
        $cursus = new Cursus();
        $user = new User();
        
        $lessons = $this->lessonRepository->findByCursusWithValidations($cursus, $user);
        
        $this->assertIsArray($lessons);
    }

    public function testFindPurchasedLessons(): void
    {
        $user = new User();
        $lessons = $this->lessonRepository->findPurchasedLessons($user);
        
        $this->assertIsArray($lessons);
    }
} 