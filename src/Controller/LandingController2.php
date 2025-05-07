<?php

// src/Controller/LandingController2.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\OffreRepository;
use App\Entity\Offre;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\RatingType;
use App\Service\PdfService;

class LandingController2 extends AbstractController
{
    #[Route('/index', name: 'landing_index')]  
    public function index(): Response
    {
        return $this->render('landing/index.html.twig');
    }

    #[Route('/about', name: 'landing_about')]  
    public function about(): Response
    {
        return $this->render('landing/about.html.twig');
    }

    #[Route('/contact', name: 'landing_contact')]  
    public function contact(): Response
    {
        return $this->render('landing/contact.html.twig');
    }

    #[Route('/service2', name: 'landing_service2')]  
    public function service(OffreRepository $offreRepository): Response
    {
        $offres = $offreRepository->findAll();
        return $this->render('landing/service2.html.twig', [
            'offres' => $offres
        ]);
    }

    #[Route('/service2/{id}', name: 'landing_service_show2')]
    public function showService(Offre $offre): Response
    {
        $form = $this->createForm(RatingType::class);

        return $this->render('landing/service_show2.html.twig', [
            'offre' => $offre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/service2/{id}/rate', name: 'landing_service_rate2', methods: ['POST'])]
    public function rate(Request $request, Offre $offre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RatingType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rating = $form->get('rating')->getData();
            $offre->addRating($rating);
            
            $entityManager->persist($offre);
            $entityManager->flush();

            $this->addFlash('success', 'Votre note a été enregistrée avec succès.');
        }

        return $this->redirectToRoute('landing_service_show2', ['id' => $offre->getId()]);
    }

    #[Route('/service2/{id}/pdf', name: 'landing_service_pdf2')]
    public function generatePdf(Offre $offre, PdfService $pdfService): Response
    {
        $pdfContent = $pdfService->generateOfferPdf($offre);

        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="offre-' . $offre->getId() . '.pdf"'
            ]
        );
    }
}