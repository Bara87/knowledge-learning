<?php

namespace App\Security\Voter;

use App\Entity\Cursus;
use App\Entity\Lesson;
use App\Entity\Purchase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CourseVoter extends Voter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'VIEW' && ($subject instanceof Cursus || $subject instanceof Lesson);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        if (!$user instanceof User) {
            return false;
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
        // Vérifier si l'utilisateur a acheté le cursus
        $purchase = $this->entityManager->getRepository(Purchase::class)
            ->findOneBy([
                'user' => $user,
                'cursus' => $cursus,
                'status' => 'completed'
            ]);

        if ($purchase) {
            $this->logger->info('Accès autorisé au cursus', [
                'user_id' => $user->getId(),
                'cursus_id' => $cursus->getId()
            ]);
            return true;
        }

        $this->logger->info('Accès refusé au cursus', [
            'user_id' => $user->getId(),
            'cursus_id' => $cursus->getId()
        ]);
        return false;
    }

    private function canViewLesson(Lesson $lesson, User $user): bool
    {
        // Si la leçon fait partie d'un cursus, vérifier si l'utilisateur a acheté le cursus
        if ($lesson->getCursus()) {
            $cursusPurchase = $this->entityManager->getRepository(Purchase::class)
                ->findOneBy([
                    'user' => $user,
                    'cursus' => $lesson->getCursus(),
                    'status' => 'completed'
                ]);

            if ($cursusPurchase) {
                $this->logger->info('Accès autorisé à la leçon via cursus', [
                    'user_id' => $user->getId(),
                    'lesson_id' => $lesson->getId(),
                    'cursus_id' => $lesson->getCursus()->getId()
                ]);
                return true;
            }
        }

        // Vérifier si l'utilisateur a acheté la leçon individuellement
        $lessonPurchase = $this->entityManager->getRepository(Purchase::class)
            ->findOneBy([
                'user' => $user,
                'lesson' => $lesson,
                'status' => 'completed'
            ]);

        if ($lessonPurchase) {
            $this->logger->info('Accès autorisé à la leçon individuelle', [
                'user_id' => $user->getId(),
                'lesson_id' => $lesson->getId()
            ]);
            return true;
        }

        $this->logger->info('Accès refusé à la leçon', [
            'user_id' => $user->getId(),
            'lesson_id' => $lesson->getId()
        ]);
        return false;
    }
}