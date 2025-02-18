<?php

namespace App\Controller;

use App\Entity\Purchase;
use App\Entity\LessonValidation;
use App\Entity\Certification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion du profil utilisateur
 * 
 * Ce contrôleur gère :
 * - L'affichage du profil utilisateur
 * - La visualisation des achats
 * - Le suivi des leçons validées
 * - L'historique des certifications
 */
#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    /**
     * Constructeur du contrôleur
     * 
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
     */
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Affiche le profil de l'utilisateur connecté
     * 
     * Cette méthode récupère et affiche :
     * - Les achats complétés de l'utilisateur
     * - Les leçons qu'il a validées
     * - Ses certifications obtenues
     * 
     * @return Response Vue du profil utilisateur
     * @throws AccessDeniedException Si l'utilisateur n'est pas connecté
     */
    #[Route('', name: 'app_profile')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer les achats complétés avec les relations
        $purchases = $this->entityManager->getRepository(Purchase::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.cursus', 'c')
            ->leftJoin('p.lesson', 'l')
            ->where('p.user = :user')
            ->andWhere('p.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Purchase::STATUS_COMPLETED)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Récupérer les leçons validées
        $lessonValidations = $this->entityManager->getRepository(LessonValidation::class)
            ->createQueryBuilder('lv')
            ->leftJoin('lv.lesson', 'l')
            ->where('lv.user = :user')
            ->setParameter('user', $user)
            ->orderBy('lv.validatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Récupérer les certifications
        $certifications = $this->entityManager->getRepository(Certification::class)
            ->createQueryBuilder('c')
            ->leftJoin('c.theme', 't')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.obtainedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('profile/index.html.twig', [
            'purchases' => $purchases,
            'lessonValidations' => $lessonValidations,
            'certifications' => $certifications
        ]);
    }
}