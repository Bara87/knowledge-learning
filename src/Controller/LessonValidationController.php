<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Service\LessonValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des validations de leçons
 * 
 * Ce contrôleur gère :
 * - L'affichage des leçons validées par l'utilisateur
 * - La validation de nouvelles leçons
 * - Le suivi de la progression des utilisateurs
 */
#[Route('/lesson-validation')]
class LessonValidationController extends AbstractController
{
    /**
     * Constructeur du contrôleur
     * 
     * @param LessonValidationService $validationService Service de gestion des validations
     */
    public function __construct(
        private LessonValidationService $validationService
    ) {}

    /**
     * Affiche la liste des leçons validées par l'utilisateur
     * 
     * Cette méthode nécessite d'être connecté (ROLE_USER)
     * 
     * @return Response Vue de la liste des validations
     * @throws AccessDeniedException Si l'utilisateur n'est pas connecté
     */
    #[Route('/', name: 'app_lesson_validations')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $validations = $this->validationService->getUserValidations($this->getUser());

        return $this->render('lesson_validation/index.html.twig', [
            'validations' => $validations,
        ]);
    }

    /**
     * Valide une leçon pour l'utilisateur connecté
     * 
     * Cette méthode :
     * - Vérifie que l'utilisateur est connecté
     * - Enregistre la validation si elle n'existe pas déjà
     * - Redirige vers la leçon avec un message de confirmation
     * 
     * @param Lesson $lesson Leçon à valider
     * @return Response Redirection vers la leçon
     * @throws AccessDeniedException Si l'utilisateur n'est pas connecté
     */
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