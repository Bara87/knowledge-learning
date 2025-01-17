<?php

namespace App\Controller;

use App\Repository\ThemeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur de la page d'accueil
 * 
 * Ce contrôleur gère l'affichage de la page d'accueil
 * et la présentation des thèmes disponibles
 */
class HomeController extends AbstractController
{
    /**
     * Affiche la page d'accueil
     * 
     * Cette méthode récupère et affiche tous les thèmes
     * disponibles dans l'application
     * 
     * @param ThemeRepository $themeRepository Repository pour accéder aux thèmes
     * @return Response Vue de la page d'accueil avec la liste des thèmes
     */
    #[Route('/', name: 'app_home')]
    public function index(ThemeRepository $themeRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'themes' => $themeRepository->findAll(),
        ]);
    }
}