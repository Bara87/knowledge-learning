<?php

namespace App\Tests\Service;

use App\Entity\Lesson;
use App\Entity\User;
use App\Service\LessonValidationService;
use App\Repository\LessonValidationRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class LessonValidationServiceTest extends TestCase
{
    private $entityManager;
    private $validationRepository;
    private $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validationRepository = $this->createMock(LessonValidationRepository::class);
        
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->entityManager;
        /** @var LessonValidationRepository $validationRepository */
        $validationRepository = $this->validationRepository;
        
        $this->service = new LessonValidationService(
            $entityManager,
            $validationRepository
        );
    }

    public function testValidateLesson(): void
    {
        $user = new User();
        $lesson = new Lesson();

        $this->validationRepository
            ->expects($this->once())
            ->method('isLessonValidatedByUser')
            ->willReturn(false);

        $result = $this->service->validateLesson($user, $lesson);
        $this->assertTrue($result);
    }
} 