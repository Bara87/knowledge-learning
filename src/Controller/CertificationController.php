<?php

namespace App\Controller;

use App\Entity\Certification;
use App\Entity\Theme;
use App\Entity\User;
use App\Repository\CertificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CertificationController extends AbstractController
{
    #[Route('/certifications', name: 'app_certification_index')]
    public function index(CertificationRepository $certificationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || !$user->isActive()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('certification/index.html.twig', [
            'certifications' => $certificationRepository->findBy(['user' => $user]),
        ]);
    }

    #[Route('/theme/{id}/certify', name: 'app_theme_certify')]
    public function certifyTheme(
        Theme $theme, 
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || !$user->isActive()) {
            throw $this->createAccessDeniedException();
        }

        if (!$user->hasCompletedTheme($theme)) {
            throw $this->createAccessDeniedException('Toutes les leçons doivent être validées');
        }

        $certification = new Certification();
        $certification->setUser($user);
        $certification->setTheme($theme);
        $certification->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($certification);
        $entityManager->flush();

        return $this->redirectToRoute('app_certification_index');
    }
}