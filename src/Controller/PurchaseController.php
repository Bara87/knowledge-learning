<?php

namespace App\Controller;

use App\Entity\Purchase;
use App\Entity\Cursus;
use App\Entity\Lesson;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PurchaseController extends AbstractController
{
    #[Route('/purchase/cursus/{id}', name: 'app_purchase_cursus')]
    public function purchaseCursus(
        Cursus $cursus,
        StripeService $stripeService,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Vérifier si l'utilisateur a déjà acheté ce cursus
        $existingPurchase = $entityManager->getRepository(Purchase::class)->findOneBy([
            'user' => $this->getUser(),
            'cursus' => $cursus,
            'status' => 'completed'
        ]);

        if ($existingPurchase) {
            $this->addFlash('warning', 'Vous avez déjà acheté ce cursus.');
            return $this->redirectToRoute('app_cursus_show', ['id' => $cursus->getId()]);
        }

        // Créer la session de paiement Stripe
        $session = $stripeService->createCursusCheckoutSession($cursus);

        return $this->redirect($session->url);
    }

    #[Route('/purchase/lesson/{id}', name: 'app_purchase_lesson')]
    public function purchaseLesson(
        Lesson $lesson,
        StripeService $stripeService,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Vérifier si l'utilisateur a déjà acheté cette leçon
        $existingPurchase = $entityManager->getRepository(Purchase::class)->findOneBy([
            'user' => $this->getUser(),
            'lesson' => $lesson,
            'status' => 'completed'
        ]);

        if ($existingPurchase) {
            $this->addFlash('warning', 'Vous avez déjà acheté cette leçon.');
            return $this->redirectToRoute('app_lesson_show', ['id' => $lesson->getId()]);
        }

        // Créer la session de paiement Stripe
        $session = $stripeService->createLessonCheckoutSession($lesson);

        return $this->redirect($session->url);
    }

    #[Route('/purchase/success', name: 'app_purchase_success')]
    public function success(): Response
    {
        $this->addFlash('success', 'Votre achat a été effectué avec succès !');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/purchase/cancel', name: 'app_purchase_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('error', 'L\'achat a été annulé.');
        return $this->redirectToRoute('app_home');
    }
}