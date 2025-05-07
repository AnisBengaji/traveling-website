<?php

namespace App\Controller;

use App\Entity\Offre;
use App\Service\GoogleYouTubeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LandingServiceController extends AbstractController
{
    #[Route('/service2/{id}', name: 'landing_service_show2')]
    public function show(Request $request, Offre $offre, GoogleYouTubeService $youtubeService): Response
    {
        // Recherche des vidéos YouTube en rapport avec l'offre
        $searchQuery = $offre->getTitre() . ' tutoriel';
        $youtubeVideos = $youtubeService->searchVideos($searchQuery, 3);

        // Gestion de la recherche personnalisée
        $searchTerm = $request->query->get('youtube_search');
        $searchResults = [];
        
        if ($searchTerm) {
            $searchResults = $youtubeService->searchVideos($searchTerm, 6);
        }

        return $this->render('landing/service_show2.html.twig', [
            'offre' => $offre,
            'youtubeVideos' => $youtubeVideos,
            'searchResults' => $searchResults,
            'searchTerm' => $searchTerm,
        ]);
    }
} 