<?php

namespace App\Repository;

use App\Entity\LessonValidation;
use App\Entity\User;
use App\Entity\Lesson;
use App\Entity\Cursus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LessonValidation>
 *
 * @method LessonValidation|null find($id, $lockMode = null, $lockVersion = null)
 * @method LessonValidation|null findOneBy(array $criteria, array $orderBy = null)
 * @method LessonValidation[]    findAll()
 * @method LessonValidation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LessonValidationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LessonValidation::class);
    }

    /**
     * Trouve toutes les validations d'un utilisateur pour un cursus donné
     */
    public function findByCursusAndUser(Cursus $cursus, User $user): array
    {
        return $this->createQueryBuilder('lv')
            ->innerJoin('lv.lesson', 'l')
            ->where('l.cursus = :cursus')
            ->andWhere('lv.user = :user')
            ->setParameter('cursus', $cursus)
            ->setParameter('user', $user)
            ->orderBy('lv.validatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si une leçon est validée par un utilisateur
     */
    public function isLessonValidatedByUser(Lesson $lesson, User $user): bool
    {
        $result = $this->createQueryBuilder('lv')
            ->select('COUNT(lv.id)')
            ->where('lv.lesson = :lesson')
            ->andWhere('lv.user = :user')
            ->setParameter('lesson', $lesson)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * Calcule le pourcentage de progression d'un utilisateur dans un cursus
     */
    public function calculateCursusProgress(Cursus $cursus, User $user): float
    {
        $totalLessons = $cursus->getLessons()->count();
        
        if ($totalLessons === 0) {
            return 0;
        }

        $validatedLessons = $this->createQueryBuilder('lv')
            ->select('COUNT(lv.id)')
            ->innerJoin('lv.lesson', 'l')
            ->where('l.cursus = :cursus')
            ->andWhere('lv.user = :user')
            ->setParameter('cursus', $cursus)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return ($validatedLessons / $totalLessons) * 100;
    }

    /**
     * Trouve les dernières validations d'un utilisateur
     */
    public function findLatestValidationsByUser(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('lv')
            ->where('lv.user = :user')
            ->setParameter('user', $user)
            ->orderBy('lv.validatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les validations pour une leçon spécifique
     */
    public function findByLesson(Lesson $lesson): array
    {
        return $this->createQueryBuilder('lv')
            ->where('lv.lesson = :lesson')
            ->setParameter('lesson', $lesson)
            ->orderBy('lv.validatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(LessonValidation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LessonValidation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}