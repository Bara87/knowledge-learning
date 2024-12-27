<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Entity\LessonValidation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/lesson-validation')]
class LessonValidationController extends AbstractController
{
    #[Route('/', name: 'app_lesson_validations')]
    #[IsGranted('ROLE_USER')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $validations = $entityManager->getRepository(LessonValidation::class)
            ->findBy(['user' => $this->getUser()], ['validatedAt' => 'DESC']);

        return $this->render('lesson_validation/index.html.twig', [
            'validations' => $validations,
        ]);
    }

    #[Route('/lesson/{id}/validate', name: 'app_validate_lesson')]
    #[IsGranted('ROLE_USER')]
    public function validateLesson(
        Lesson $lesson,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier si la leçon n'est pas déjà validée
        $existingValidation = $entityManager->getRepository(LessonValidation::class)
            ->findOneBy([
                'user' => $this->getUser(),
                'lesson' => $lesson
            ]);

        if (!$existingValidation) {
            // Créer une nouvelle validation
            $validation = new LessonValidation();
            $validation->setUser($this->getUser());
            $validation->setLesson($lesson);
            
            $entityManager->persist($validation);
            $entityManager->flush();

            $this->addFlash('success', 'Leçon validée avec succès !');
        }

        return $this->redirectToRoute('app_lesson_show', ['id' => $lesson->getId()]);
    }
}