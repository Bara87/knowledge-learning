<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Service\LessonValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/lesson-validation')]
class LessonValidationController extends AbstractController
{
    public function __construct(
        private LessonValidationService $validationService
    ) {}

    #[Route('/', name: 'app_lesson_validations')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $validations = $this->validationService->getUserValidations($this->getUser());

        return $this->render('lesson_validation/index.html.twig', [
            'validations' => $validations,
        ]);
    }

    #[Route('/lesson/{id}/validate', name: 'app_validate_lesson')]
    #[IsGranted('ROLE_USER')]
    public function validateLesson(Lesson $lesson): Response
    {
        if ($this->validationService->validateLesson($this->getUser(), $lesson)) {
            $this->addFlash('success', 'Leçon validée avec succès !');
        } else {
            $this->addFlash('info', 'Cette leçon a déjà été validée.');
        }

        return $this->redirectToRoute('app_lesson_show', ['id' => $lesson->getId()]);
    }
}