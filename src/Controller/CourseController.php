<?php

namespace App\Controller;

use App\Entity\Theme;
use App\Entity\Cursus;
use App\Entity\Lesson;
use App\Entity\Purchase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des cours
 * 
 * Ce contrôleur gère :
 * - L'affichage des thèmes
 * - L'affichage des cursus
 * - L'affichage des leçons
 * - La gestion des accès aux contenus
 */
class CourseController extends AbstractController
{
    /**
     * Affiche la page d'accueil des cours
     * 
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
     * @return Response Vue de la liste des thèmes
     */
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $themes = $entityManager->getRepository(Theme::class)->findAll();

        return $this->render('course/index.html.twig', [
            'themes' => $themes,
        ]);
    }

    /**
     * Affiche le détail d'un thème
     * 
     * Les thèmes sont toujours accessibles sans restriction
     * 
     * @param Theme $theme Thème à afficher
     * @return Response Vue du détail du thème
     */
    #[Route('/theme/{id}', name: 'app_theme_show')]
    public function showTheme(Theme $theme): Response
    {
        // Les thèmes sont toujours visibles, pas besoin de vérification d'accès
        return $this->render('course/theme.html.twig', [
            'theme' => $theme,
        ]);
    }

    /**
     * Affiche le détail d'un cursus
     * 
     * Vérifie les droits d'accès et :
     * - Redirige vers la connexion si non connecté
     * - Affiche une prévisualisation si non acheté
     * - Affiche le contenu complet si accès autorisé
     * 
     * @param Cursus $cursus Cursus à afficher
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
     * @return Response Vue du cursus ou redirection
     */
    #[Route('/cursus/{id}', name: 'app_cursus_show')]
    public function showCursus(
        Cursus $cursus,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isGranted('VIEW', $cursus)) {
            if (!$this->isGranted('ROLE_USER')) {
                $this->addFlash('warning', 'Veuillez vous connecter pour accéder à ce cursus.');
                return $this->redirectToRoute('app_login');
            }
            
            // Vérifier si l'utilisateur a déjà acheté ce cursus
            $purchase = $entityManager->getRepository(Purchase::class)->findOneBy([
                'user' => $this->getUser(),
                'cursus' => $cursus,
                'status' => Purchase::STATUS_COMPLETED
            ]);

            if ($purchase) {
                return $this->render('course/cursus.html.twig', [
                    'cursus' => $cursus,
                ]);
            }
            
            return $this->render('course/cursus_preview.html.twig', [
                'cursus' => $cursus,
                'needsPurchase' => true
            ]);
        }

        return $this->render('course/cursus.html.twig', [
            'cursus' => $cursus,
        ]);
    }

    /**
     * Affiche le détail d'une leçon
     * 
     * Vérifie les droits d'accès et :
     * - Redirige vers la connexion si non connecté
     * - Affiche une prévisualisation si non achetée
     * - Affiche le contenu si la leçon ou le cursus parent est acheté
     * 
     * @param Lesson $lesson Leçon à afficher
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
     * @return Response Vue de la leçon ou redirection
     */
    #[Route('/lesson/{id}', name: 'app_lesson_show')]
    public function showLesson(
        Lesson $lesson,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isGranted('VIEW', $lesson)) {
            if (!$this->isGranted('ROLE_USER')) {
                $this->addFlash('warning', 'Veuillez vous connecter pour accéder à cette leçon.');
                return $this->redirectToRoute('app_login');
            }

            // Vérifier si le cursus parent est déjà acheté
            if ($lesson->getCursus()) {
                $cursusPurchase = $entityManager->getRepository(Purchase::class)->findOneBy([
                    'user' => $this->getUser(),
                    'cursus' => $lesson->getCursus(),
                    'status' => Purchase::STATUS_COMPLETED
                ]);

                if ($cursusPurchase) {
                    return $this->render('course/lesson.html.twig', [
                        'lesson' => $lesson,
                    ]);
                }
            }

            // Vérifier si la leçon individuelle est achetée
            $lessonPurchase = $entityManager->getRepository(Purchase::class)->findOneBy([
                'user' => $this->getUser(),
                'lesson' => $lesson,
                'status' => Purchase::STATUS_COMPLETED
            ]);

            if ($lessonPurchase) {
                return $this->render('course/lesson.html.twig', [
                    'lesson' => $lesson,
                ]);
            }
            
            return $this->render('course/lesson_preview.html.twig', [
                'lesson' => $lesson,
                'needsPurchase' => true
            ]);
        }

        return $this->render('course/lesson.html.twig', [
            'lesson' => $lesson,
        ]);
    }

    /**
     * Affiche la liste des leçons d'un cursus
     * 
     * Vérifie les droits d'accès et :
     * - Redirige vers la connexion si non connecté
     * - Redirige vers l'achat si cursus non acheté
     * - Affiche la liste complète si accès autorisé
     * 
     * @param Cursus $cursus Cursus dont on veut voir les leçons
     * @return Response Vue de la liste des leçons ou redirection
     */
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