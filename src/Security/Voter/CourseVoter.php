<?php

namespace App\Security\Voter;

use App\Entity\Cursus;
use App\Entity\Lesson;
use App\Entity\Purchase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CourseVoter extends Voter
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'VIEW' && 
               ($subject instanceof Cursus || $subject instanceof Lesson);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        if (!$user instanceof User) {
            return false;
        }

        // Si admin, accès total
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        if ($subject instanceof Cursus) {
            return $this->canViewCursus($subject, $user);
        }

        if ($subject instanceof Lesson) {
            return $this->canViewLesson($subject, $user);
        }

        return false;
    }

    private function canViewCursus(Cursus $cursus, User $user): bool
    {
        // Si cursus gratuit
        if ($cursus->getPrice() <= 0) {
            return true;
        }

        // Vérifier l'achat du cursus
        $purchase = $this->entityManager->getRepository(Purchase::class)
            ->findOneBy([
                'user' => $user,
                'cursus' => $cursus,
                'status' => 'completed'
            ]);

        return $purchase !== null;
    }

    private function canViewLesson(Lesson $lesson, User $user): bool
    {
        // Si leçon gratuite
        if ($lesson->getPrice() <= 0) {
            return true;
        }

        // Vérifier si la leçon fait partie d'un cursus acheté
        if ($lesson->getCursus()) {
            $cursusPurchase = $this->entityManager->getRepository(Purchase::class)
                ->findOneBy([
                    'user' => $user,
                    'cursus' => $lesson->getCursus(),
                    'status' => 'completed'
                ]);

            if ($cursusPurchase) {
                return true;
            }
        }

        // Vérifier l'achat individuel de la leçon
        $lessonPurchase = $this->entityManager->getRepository(Purchase::class)
            ->findOneBy([
                'user' => $user,
                'lesson' => $lesson,
                'status' => 'completed'
            ]);

        return $lessonPurchase !== null;
    }
}