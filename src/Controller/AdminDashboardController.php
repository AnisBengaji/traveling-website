<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Entity\Offre;
use App\Form\OffreType;
use App\Repository\OffreRepository;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\TutorialRepository;
use Knp\Snappy\Pdf;

#[IsGranted('ROLE_ADMIN')]

class AdminDashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function index(): Response
    {
     
        $welcomeMessage = [
            'title' => 'Welcome to Trip In Admin',
            'message' => 'Manage   efficiently from here.',
        ];

        $services = [
            ['title' => 'Destinations', 'description' => 'Manage travel destinations and their details.', 'link' => '#'],
            ['title' => 'offres', 'description' => 'discover our offres.', 'link' => '#'],
            ['title' => 'reservation', 'description' => 'Handle  bookings and availability.', 'link' => '#'],
            ['title' => 'Events', 'description' => 'Organize and monitor upcoming events.', 'link' => '#'],
            ['title' => 'community', 'description' => 'chat with other users .', 'link' => '#'],
        ];

        $stats = [
            'clients' => 150,
            'publications' => 45,
            
        ];

        $feedback = [
            ['comment' => 'Great service, very reliable!', 'author' => 'Anis bengaji'],
            ['comment' => 'The best travel experience I\'ve had.', 'author' => 'khaled abed'],
            ['comment' => 'Quick and efficient support.', 'author' => 'ines missoui'],
            ['comment' => 'Quick and efficient support.', 'author' => 'eminem'],
        ];

        return $this->render('admin/admin_dashboard.html.twig', [
            'welcome' => $welcomeMessage,
            'services' => $services,
            'stats' => $stats,
            'feedback' => $feedback,
        ]);
    }

    #[Route('/offre', name: 'app_offre')]
    public function offre(Request $request, EntityManagerInterface $entityManager, OffreRepository $offreRepository): Response
    {
        $offre = new Offre();
        $form = $this->createForm(OffreType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($offre);
            $entityManager->flush();

            return $this->redirectToRoute('app_offre', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/offre.html.twig', [
            'controller_name' => 'HomeController',
            'form' => $form,
            'offre' => $offreRepository->findAll(),
        ]);
    }

    #[Route('/offre/export-pdf', name: 'admin_offre_export_pdf')]
    public function exportOffresPdf(OffreRepository $offreRepository, TutorialRepository $tutorialRepository, Pdf $knpSnappyPdf): Response
    {
        $offres = $offreRepository->findAll();
        $tutorials = $tutorialRepository->findAllOrderedByDateDebut();
        $html = $this->renderView('admin/export_offres_pdf.html.twig', [
            'offres' => $offres,
            'tutorials' => $tutorials,
        ]);
        $filename = 'offres_tutorials_' . date('Ymd_His') . '.pdf';
        return new Response(
            $knpSnappyPdf->getOutputFromHtml($html),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]
        );
    }
}