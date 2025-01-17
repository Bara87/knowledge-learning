<?php

namespace App\Service;

use App\Entity\Lesson;
use App\Entity\Cursus;
use App\Entity\User;
use App\Entity\LessonValidation;
use App\Repository\LessonValidationRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion des validations de leçons
 * 
 * Ce service gère :
 * - La validation des leçons par les utilisateurs
 * - Le calcul de la progression dans les cursus
 * - Le suivi des validations par utilisateur
 */
class LessonValidationService
{
    /**
     * Constructeur du service
     * 
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités
     * @param LessonValidationRepository $validationRepository Repository des validations
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LessonValidationRepository $validationRepository
    ) {}

    /**
     * Valide une leçon pour un utilisateur
     * 
     * @param User $user Utilisateur qui valide la leçon
     * @param Lesson $lesson Leçon à valider
     * @return bool True si la validation a réussi, False si la leçon était déjà validée
     */
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

    /**
     * Calcule la progression d'un utilisateur dans un cursus
     * 
     * @param User $user Utilisateur concerné
     * @param Cursus $cursus Cursus à évaluer
     * @return float Pourcentage de progression (0-100)
     */
    public function getCursusProgress(User $user, Cursus $cursus): float
    {
        return $this->validationRepository->calculateCursusProgress($cursus, $user);
    }

    /**
     * Récupère toutes les validations d'un utilisateur
     * 
     * @param User $user Utilisateur concerné
     * @return array Liste des validations triées par date décroissante
     */
    public function getUserValidations(User $user): array
    {
        return $this->validationRepository->findBy(
            ['user' => $user],
            ['validatedAt' => 'DESC']
        );
    }
}