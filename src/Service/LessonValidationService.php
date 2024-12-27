<?php

namespace App\Service;

use App\Entity\Lesson;
use App\Entity\Cursus;
use App\Entity\User;
use App\Entity\LessonValidation;
use App\Repository\LessonValidationRepository;
use Doctrine\ORM\EntityManagerInterface;

class LessonValidationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LessonValidationRepository $validationRepository
    ) {}

    public function validateLesson(User $user, Lesson $lesson): bool
    {
        // Vérifier si la leçon n'est pas déjà validée
        if ($this->validationRepository->isLessonValidatedByUser($lesson, $user)) {
            return false;
        }

        // Créer une nouvelle validation
        $validation = new LessonValidation();
        $validation->setUser($user);
        $validation->setLesson($lesson);

        $this->entityManager->persist($validation);
        $this->entityManager->flush();

        return true;
    }

    public function getCursusProgress(User $user, Cursus $cursus): float
    {
        return $this->validationRepository->calculateCursusProgress($cursus, $user);
    }

    public function getUserValidations(User $user): array
    {
        return $this->validationRepository->findBy(
            ['user' => $user],
            ['validatedAt' => 'DESC']
        );
    }
}