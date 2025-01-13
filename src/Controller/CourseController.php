<?php

namespace App\Controller;

use App\Entity\Theme;
use App\Entity\Cursus;
use App\Entity\Lesson;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CourseController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $themes = $entityManager->getRepository(Theme::class)->findAll();

        return $this->render('course/index.html.twig', [
            'themes' => $themes,
        ]);
    }

    #[Route('/theme/{id}', name: 'app_theme_show')]
    public function showTheme(Theme $theme): Response
    {
        // Les thèmes sont toujours visibles, pas besoin de vérification d'accès
        return $this->render('course/theme.html.twig', [
            'theme' => $theme,
        ]);
    }

    #[Route('/cursus/{id}', name: 'app_cursus_show')]
    public function showCursus(Cursus $cursus): Response
    {
        // Vérifier si l'utilisateur est connecté et a les droits d'accès
        if (!$this->isGranted('VIEW', $cursus)) {
            if (!$this->isGranted('ROLE_USER')) {
                $this->addFlash('warning', 'Veuillez vous connecter pour accéder à ce cursus.');
                return $this->redirectToRoute('app_login');
            }
            
            // Au lieu de rediriger, on affiche la page avec un message d'achat
            return $this->render('course/cursus_preview.html.twig', [
                'cursus' => $cursus,
                'needsPurchase' => true
            ]);
        }

        return $this->render('course/cursus.html.twig', [
            'cursus' => $cursus,
        ]);
    }

    #[Route('/lesson/{id}', name: 'app_lesson_show')]
    public function showLesson(Lesson $lesson): Response
    {
        // Vérifier si l'utilisateur est connecté et a les droits d'accès
        if (!$this->isGranted('VIEW', $lesson)) {
            if (!$this->isGranted('ROLE_USER')) {
                $this->addFlash('warning', 'Veuillez vous connecter pour accéder à cette leçon.');
                return $this->redirectToRoute('app_login');
            }

            // Si le cursus parent est déjà acheté, pas besoin d'acheter la leçon individuellement
            if ($lesson->getCursus() && $this->isGranted('VIEW', $lesson->getCursus())) {
                return $this->render('course/lesson.html.twig', [
                    'lesson' => $lesson,
                ]);
            }
            
            // Au lieu de rediriger, on affiche la page avec un message d'achat
            return $this->render('course/lesson_preview.html.twig', [
                'lesson' => $lesson,
                'needsPurchase' => true
            ]);
        }

        return $this->render('course/lesson.html.twig', [
            'lesson' => $lesson,
        ]);
    }

    #[Route('/cursus/{id}/lessons', name: 'app_cursus_lessons')]
    public function cursusLessons(Cursus $cursus): Response
    {
        if (!$this->isGranted('VIEW', $cursus)) {
            if (!$this->isGranted('ROLE_USER')) {
                $this->addFlash('warning', 'Veuillez vous connecter pour accéder aux leçons.');
                return $this->redirectToRoute('app_login');
            }

            $this->addFlash('info', 'Vous devez acheter ce cursus pour accéder aux leçons.');
            return $this->redirectToRoute('app_purchase_cursus', [
                'id' => $cursus->getId()
            ]);
        }

        return $this->render('course/cursus_lessons.html.twig', [
            'cursus' => $cursus,
            'lessons' => $cursus->getLessons()
        ]);
    }

   
}