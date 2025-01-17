<?php

namespace App\Controller;

use App\Entity\Certification;
use App\Entity\Theme;
use App\Entity\User;
use App\Repository\CertificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des certifications
 * 
 * Ce contrôleur gère :
 * - L'affichage des certifications de l'utilisateur
 * - La création de nouvelles certifications
 * - Le téléchargement des certificats en PDF
 */
#[Route('/certifications')]
class CertificationController extends AbstractController
{
    /**
     * Constructeur du contrôleur
     * 
     * @param CertificationRepository $certificationRepository Repository des certifications
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
     */
    public function __construct(
        private CertificationRepository $certificationRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Affiche la liste des certifications de l'utilisateur
     * 
     * Cette méthode :
     * - Récupère tous les thèmes complétés par l'utilisateur
     * - Crée automatiquement les certifications manquantes
     * - Affiche la liste des certifications obtenues
     * 
     * @return Response Vue de la liste des certifications
     * @throws AccessDeniedException Si l'utilisateur n'est pas connecté
     */
    #[Route('', name: 'app_certification_index')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Récupérer tous les thèmes complétés par l'utilisateur
        $completedThemes = [];
        $themes = $this->entityManager->getRepository(Theme::class)->findAll();
        
        foreach ($themes as $theme) {
            if ($user->hasCompletedTheme($theme)) {
                // Vérifier si une certification existe déjà
                $certification = $this->certificationRepository->findOneBy([
                    'user' => $user,
                    'theme' => $theme
                ]);

                // Si non, créer une nouvelle certification
                if (!$certification) {
                    $certification = new Certification();
                    $certification->setUser($user)
                                ->setTheme($theme)
                                ->setCreatedAt(new \DateTimeImmutable())
                                ->setObtainedAt(new \DateTimeImmutable());
                    
                    $this->entityManager->persist($certification);
                    $this->entityManager->flush();
                }

                $completedThemes[] = $certification;
            }
        }

        return $this->render('certification/index.html.twig', [
            'certifications' => $completedThemes
        ]);
    }

    /**
     * Certifie un thème pour l'utilisateur connecté
     * 
     * Cette méthode :
     * - Vérifie que toutes les leçons du thème sont validées
     * - Crée une nouvelle certification
     * - Redirige vers la liste des certifications
     * 
     * @param Theme $theme Thème à certifier
     * @param EntityManagerInterface $entityManager Gestionnaire d'entités
     * @return Response Redirection vers la liste des certifications
     * @throws AccessDeniedException Si l'utilisateur n'est pas connecté ou n'a pas validé toutes les leçons
     */
    #[Route('/theme/{id}/certify', name: 'app_theme_certify')]
    #[IsGranted('ROLE_USER')]
    public function certifyTheme(Theme $theme, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->hasCompletedTheme($theme)) {
            throw $this->createAccessDeniedException('Toutes les leçons doivent être validées');
        }

        $certification = new Certification();
        $certification->setUser($user);
        $certification->setTheme($theme);
        $certification->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($certification);
        $entityManager->flush();

        $this->addFlash('success', 'Félicitations ! Vous avez obtenu votre certification.');

        return $this->redirectToRoute('app_certification_index');
    }

    /**
     * Génère et télécharge le certificat en PDF
     * 
     * Cette méthode :
     * - Vérifie que l'utilisateur a complété le thème
     * - Crée ou récupère la certification
     * - Génère le PDF du certificat
     * - Envoie le fichier en téléchargement
     * 
     * @param Theme $theme Thème concerné par le certificat
     * @return Response Fichier PDF du certificat
     * @throws AccessDeniedException Si l'utilisateur n'a pas complété le thème
     */
    #[Route('/download/{theme}', name: 'app_certification_download')]
    #[IsGranted('ROLE_USER')]
    public function download(Theme $theme): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier si l'utilisateur a complété le thème
        if (!$user->hasCompletedTheme($theme)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas encore complété ce thème');
        }

        // Récupérer ou créer la certification
        $certification = $this->certificationRepository->findOneBy([
            'user' => $user,
            'theme' => $theme
        ]);

        if (!$certification) {
            $certification = new Certification();
            $certification->setUser($user)
                        ->setTheme($theme)
                        ->setCreatedAt(new \DateTimeImmutable())
                        ->setObtainedAt(new \DateTimeImmutable());
            
            $this->entityManager->persist($certification);
            $this->entityManager->flush();
        }

        // Configuration de DOMPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->setIsRemoteEnabled(true);

        $dompdf = new Dompdf($options);

        // Génération du HTML du certificat
        $html = $this->renderView('certification/certificate_pdf.html.twig', [
            'user' => $user,
            'theme' => $theme,
            'date' => new \DateTime(),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Génération du nom du fichier
        $filename = sprintf('certificat_%s_%s.pdf',
            $this->slugify($theme->getName()),
            (new \DateTime())->format('Y-m-d')
        );

        // Envoi du PDF
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }

    /**
     * Convertit une chaîne en slug (pour le nom de fichier)
     * 
     * @param string $text Texte à convertir en slug
     * @return string Texte converti en slug
     */
    private function slugify(string $text): string
    {
        // Remplace les caractères non alphanumériques par des tirets
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        // Translitère
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // Supprime les caractères indésirables
        $text = preg_replace('~[^-\w]+~', '', $text);
        // Convertit en minuscules
        $text = strtolower($text);
        // Supprime les tirets en début et fin
        $text = trim($text, '-');
        
        return empty($text) ? 'n-a' : $text;
    }
}