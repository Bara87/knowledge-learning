<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Theme;
use App\Entity\Cursus;
use App\Entity\Lesson;
use App\Form\ThemeType;
use App\Form\CursusType;
use App\Form\LessonType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur de gestion de l'interface d'administration
 * 
 * Ce contrôleur gère toutes les fonctionnalités d'administration :
 * - Tableau de bord administrateur
 * - Gestion des utilisateurs (liste, activation/désactivation)
 * - Gestion des thèmes (création, modification)
 * - Gestion des cursus (création, modification)
 * - Gestion des leçons (création, modification)
 */
#[Route('/admin')]
class AdminController extends AbstractController
{
    /**
     * Affiche le tableau de bord administrateur
     * 
     * Récupère et affiche :
     * - La liste de tous les utilisateurs
     * - La liste de tous les thèmes
     * - La liste de tous les cursus
     * - La liste de toutes les leçons
     * 
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
     * @return Response Vue du tableau de bord
     * @throws AccessDeniedException Si l'utilisateur n'a pas le rôle ROLE_ADMIN
     */
    #[Route('/', name: 'app_admin_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $entityManager->getRepository(User::class)->findAll();
        $themes = $entityManager->getRepository(Theme::class)->findAll();
        $cursus = $entityManager->getRepository(Cursus::class)->findAll();
        $lessons = $entityManager->getRepository(Lesson::class)->findAll();

        return $this->render('admin/dashboard.html.twig', [
            'users' => $users,
            'themes' => $themes,
            'cursus' => $cursus,
            'lessons' => $lessons,
        ]);
    }

    /**
     * Affiche la liste des utilisateurs
     * 
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
     * @return Response Vue de la liste des utilisateurs
     * @throws AccessDeniedException Si l'utilisateur n'a pas le rôle ROLE_ADMIN
     */
    #[Route('/users', name: 'app_admin_users')]
    public function users(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * Active ou désactive un utilisateur
     * 
     * Inverse l'état actif/inactif d'un utilisateur et redirige vers la liste des utilisateurs
     * 
     * @param User $user Utilisateur à modifier
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
     * @return Response Redirection vers la liste des utilisateurs
     * @throws AccessDeniedException Si l'utilisateur n'a pas le rôle ROLE_ADMIN
     */
    #[Route('/user/{id}/toggle-active', name: 'app_admin_user_toggle')]
    public function toggleUserActive(
        User $user,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user->setIsActive(!$user->isActive());
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_users');
    }

    /**
     * Crée ou modifie un thème
     * 
     * Affiche et traite le formulaire de création/modification d'un thème
     * En cas de succès, redirige vers le tableau de bord
     * 
     * @param Request $request Requête HTTP
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
     * @param Theme|null $theme Thème à modifier (null pour création)
     * @return Response Vue du formulaire ou redirection
     * @throws AccessDeniedException Si l'utilisateur n'a pas le rôle ROLE_ADMIN
     */
    #[Route('/theme/new', name: 'app_admin_theme_new')]
    #[Route('/theme/{id}/edit', name: 'app_admin_theme_edit')]
    public function themeForm(
        Request $request,
        EntityManagerInterface $entityManager,
        ?Theme $theme = null
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $theme = $theme ?? new Theme();
        $form = $this->createForm(ThemeType::class, $theme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($theme);
            $entityManager->flush();

            $this->addFlash('success', 'Le thème a été sauvegardé.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/theme_form.html.twig', [
            'form' => $form->createView(),
            'theme' => $theme,
        ]);
    }

    /**
     * Crée ou modifie un cursus
     * 
     * Affiche et traite le formulaire de création/modification d'un cursus
     * En cas de succès, redirige vers le tableau de bord
     * 
     * @param Request $request Requête HTTP
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
     * @param Cursus|null $cursus Cursus à modifier (null pour création)
     * @return Response Vue du formulaire ou redirection
     * @throws AccessDeniedException Si l'utilisateur n'a pas le rôle ROLE_ADMIN
     */
    #[Route('/cursus/new', name: 'app_admin_cursus_new')]
    #[Route('/cursus/{id}/edit', name: 'app_admin_cursus_edit')]
    public function cursusForm(
        Request $request,
        EntityManagerInterface $entityManager,
        ?Cursus $cursus = null
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $cursus = $cursus ?? new Cursus();
        $form = $this->createForm(CursusType::class, $cursus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($cursus);
            $entityManager->flush();

            $this->addFlash('success', 'Le cursus a été sauvegardé.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/cursus_form.html.twig', [
            'form' => $form->createView(),
            'cursus' => $cursus,
        ]);
    }

    /**
     * Crée ou modifie une leçon
     * 
     * Affiche et traite le formulaire de création/modification d'une leçon
     * En cas de succès, redirige vers le tableau de bord
     * 
     * @param Request $request Requête HTTP
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
     * @param Lesson|null $lesson Leçon à modifier (null pour création)
     * @return Response Vue du formulaire ou redirection
     * @throws AccessDeniedException Si l'utilisateur n'a pas le rôle ROLE_ADMIN
     */
    #[Route('/lesson/new', name: 'app_admin_lesson_new')]
    #[Route('/lesson/{id}/edit', name: 'app_admin_lesson_edit')]
    public function lessonForm(
        Request $request,
        EntityManagerInterface $entityManager,
        ?Lesson $lesson = null
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $lesson = $lesson ?? new Lesson();
        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($lesson);
            $entityManager->flush();

            $this->addFlash('success', 'La leçon a été sauvegardée.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/lesson_form.html.twig', [
            'form' => $form->createView(),
            'lesson' => $lesson,
        ]);
    }
}