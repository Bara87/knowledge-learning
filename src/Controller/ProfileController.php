<?php

namespace App\Controller;

use App\Entity\Purchase;
use App\Entity\LessonValidation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'app_profile')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer les achats complétés
        $purchases = $this->entityManager->getRepository(Purchase::class)
            ->findBy([
                'user' => $user,
                'status' => 'completed'
            ]);

        // Récupérer les leçons validées
        $lessonValidations = $this->entityManager->getRepository(LessonValidation::class)
            ->findBy(['user' => $user]);

        // Récupérer les certifications
        $certifications = $this->entityManager->getRepository('App:Certification')
            ->findBy(['user' => $user]);

        return $this->render('profile/index.html.twig', [
            'purchases' => $purchases,
            'lessonValidations' => $lessonValidations,
            'certifications' => $certifications
        ]);
    }
}