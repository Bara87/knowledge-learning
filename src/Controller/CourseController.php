<?php

namespace App\Controller;

use App\Entity\Theme;
use App\Entity\Cursus;
use App\Entity\Lesson;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
        return $this->render('course/theme.html.twig', [
            'theme' => $theme,
        ]);
    }

    #[Route('/cursus/{id}', name: 'app_cursus_show')]
    public function showCursus(Cursus $cursus): Response
    {
        return $this->render('course/cursus.html.twig', [
            'cursus' => $cursus,
        ]);
    }

    #[Route('/lesson/{id}', name: 'app_lesson_show')]
    public function showLesson(Lesson $lesson): Response
    {
        // Vérifier si l'utilisateur a acheté la leçon
        $this->denyAccessUnlessGranted('VIEW', $lesson);

        return $this->render('course/lesson.html.twig', [
            'lesson' => $lesson,
        ]);
    }
}