<?php

namespace App\Repository;

use App\Entity\Lesson;
use App\Entity\User;
use App\Entity\Cursus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Lesson>
 *
 * @method Lesson|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lesson|null findOneBy(array $criteria, array $orderBy = null)
 * @method Lesson[]    findAll()
 * @method Lesson[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LessonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lesson::class);
    }

    /**
     * Trouve toutes les leçons d'un cursus avec leurs validations pour un utilisateur
     */
    public function findByCursusWithValidations(Cursus $cursus, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.cursus = :cursus')
            ->setParameter('cursus', $cursus)
            ->orderBy('l.createdAt', 'ASC');

        if ($user) {
            $qb->leftJoin('l.validations', 'v', 'WITH', 'v.user = :user')
               ->addSelect('v')
               ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les leçons achetées par un utilisateur
     */
    public function findPurchasedLessons(User $user): array
    {
        return $this->createQueryBuilder('l')
            ->innerJoin('l.purchases', 'p')
            ->where('p.user = :user')
            ->andWhere('p.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'paid')
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les leçons disponibles (non achetées) pour un utilisateur
     */
    public function findAvailableLessons(User $user): array
    {
        $purchasedLessons = $this->findPurchasedLessons($user);
        $purchasedLessonIds = array_map(fn($lesson) => $lesson->getId(), $purchasedLessons);

        $qb = $this->createQueryBuilder('l');
        
        if (!empty($purchasedLessonIds)) {
            $qb->where($qb->expr()->notIn('l.id', $purchasedLessonIds));
        }

        return $qb->orderBy('l.createdAt', 'DESC')
                 ->getQuery()
                 ->getResult();
    }

    /**
     * Recherche des leçons par titre ou contenu
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.title LIKE :query')
            ->orWhere('l.content LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Lesson $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Lesson $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}