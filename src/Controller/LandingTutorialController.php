<?php

namespace App\Controller;

use App\Entity\Tutorial;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LandingTutorialController extends AbstractController
{
    #[Route('/tutorial/{id}/details', name: 'landing_tutorial_details')]
    public function details(Tutorial $tutorial): Response
    {
        return $this->render('landing/tutorial_details.html.twig', [
            'tutorial' => $tutorial,
        ]);
    }
} 