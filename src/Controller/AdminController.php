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

#[Route('/admin')]
class AdminController extends AbstractController
{
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

    #[Route('/users', name: 'app_admin_users')]
    public function users(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

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