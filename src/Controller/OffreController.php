<?php
// src/Controller/OffreController.php
namespace App\Controller;

use App\Entity\Offre;
use App\Form\OffreType;
use App\Repository\OffreRepository;
use App\Service\NewsletterService;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Repository\TutorialRepository;
use App\Service\PdfService;
use Knp\Snappy\Pdf;

#[Route('/offre')]
class OffreController extends AbstractController
{
    #[Route('/', name: 'app_offre_index', methods: ['GET'])]
    public function index(OffreRepository $offreRepository): Response
    {
        $offres = $offreRepository->findAll();
        if (empty($offres)) {
            $this->addFlash('warning', 'Aucune offre n\'est disponible pour le moment.');
        }
        
        return $this->render('offre/index.html.twig', [
            'offres' => $offres,
        ]);
    }

    #[Route('/new', name: 'app_offre_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        SluggerInterface $slugger,
        NewsletterService $newsletterService,
        SmsService $smsService
    ): Response {
        $offre = new Offre();
        $form = $this->createForm(OffreType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image');
                }

                $offre->setImage($newFilename);
            }

            try {
                $entityManager->persist($offre);
                $entityManager->flush();

                // Envoi des notifications
                try {
                    $newsletterService->sendNewOfferNotification($offre);
                    $smsService->sendNewOfferNotification($offre);
                    $this->addFlash('success', 'L\'offre a été créée avec succès et les notifications ont été envoyées.');
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'L\'offre a été créée mais l\'envoi des notifications a échoué : ' . $e->getMessage());
                }

                return $this->redirectToRoute('app_offre_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la création de l\'offre : ' . $e->getMessage());
            }
        }

        return $this->render('offre/new.html.twig', [
            'offre' => $offre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_offre_show', methods: ['GET'])]
    public function show(?Offre $offre): Response
    {
        if (!$offre) {
            $this->addFlash('error', 'Offre non trouvée.');
            return $this->redirectToRoute('app_offre_index');
        }

        return $this->render('offre/show.html.twig', [
            'offre' => $offre,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_offre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Offre $offre, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(OffreType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    // Supprimer l'ancienne image si elle existe
                    if ($offre->getImage()) {
                        $oldImagePath = $this->getParameter('images_directory').'/'.$offre->getImage();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }

                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image');
                }

                $offre->setImage($newFilename);
            }

            try {
                $entityManager->flush();
                $this->addFlash('success', 'L\'offre a été modifiée avec succès.');
                return $this->redirectToRoute('app_offre');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la modification : ' . $e->getMessage());
            }
        }

        return $this->render('offre/edit.html.twig', [
            'offre' => $offre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_offre_delete', methods: ['POST'])]
    public function delete(Request $request, ?Offre $offre, EntityManagerInterface $entityManager): Response
    {
        if (!$offre) {
            $this->addFlash('error', 'Offre non trouvée.');
            return $this->redirectToRoute('app_offre_index');
        }

        if (!$this->isCsrfTokenValid('delete'.$offre->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_offre_index');
        }

        try {
            $entityManager->remove($offre);
            $entityManager->flush();
            $this->addFlash('success', 'L\'offre a été supprimée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_offre_index');
    }

    #[Route('/export-pdf', name: 'app_offre_export_pdf')]
    public function exportPdf(OffreRepository $offreRepository, TutorialRepository $tutorialRepository, Pdf $knpSnappyPdf): Response
    {
        $offres = $offreRepository->findAll();
        $tutorials = $tutorialRepository->findAll();

        $html = $this->renderView('admin/export_offres_pdf.html.twig', [
            'offres' => $offres,
            'tutorials' => $tutorials
        ]);

        $filename = 'offres_tutorials_' . date('Ymd_His') . '.pdf';

        // Configuration des options pour wkhtmltopdf
        $knpSnappyPdf->setOption('encoding', 'utf-8');
        $knpSnappyPdf->setOption('enable-local-file-access', true);
        $knpSnappyPdf->setOption('no-outline', true);
        $knpSnappyPdf->setOption('margin-top', 10);
        $knpSnappyPdf->setOption('margin-right', 10);
        $knpSnappyPdf->setOption('margin-bottom', 10);
        $knpSnappyPdf->setOption('margin-left', 10);

        return new Response(
            $knpSnappyPdf->getOutputFromHtml($html),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]
        );
    }

    #[Route('/{id}/rate', name: 'app_offre_rate', methods: ['POST'])]
    public function rate(Request $request, Offre $offre, EntityManagerInterface $entityManager): Response
    {
        try {
            $session = $request->getSession();
            $ratedOffers = $session->get('rated_offers', []);
            
            // Vérifier si l'utilisateur a déjà noté cette offre
            if (in_array($offre->getId(), $ratedOffers)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Vous avez déjà noté cette offre'
                ], 400);
            }

            $rating = (int) $request->request->get('rating');
            
            // Validation de la note
            if ($rating < 1 || $rating > 5) {
                return $this->json([
                    'success' => false,
                    'error' => 'La note doit être comprise entre 1 et 5'
                ], 400);
            }

            // Calcul de la nouvelle moyenne
            $currentRating = $offre->getRating() ?? 0;
            $currentCount = $offre->getRatingCount() ?? 0;
            
            $newCount = $currentCount + 1;
            $newRating = (($currentRating * $currentCount) + $rating) / $newCount;
            
            // Mise à jour de l'offre
            $offre->setRating($newRating);
            $offre->setRatingCount($newCount);
            
            $entityManager->flush();
            
            // Ajouter l'offre aux offres notées dans la session
            $ratedOffers[] = $offre->getId();
            $session->set('rated_offers', $ratedOffers);
            
            return $this->json([
                'success' => true,
                'newRating' => round($newRating, 1),
                'ratingCount' => $newCount
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Une erreur est survenue lors de la notation'
            ], 500);
        }
    }

    #[Route('/{id}/check-rating', name: 'app_offre_check_rating', methods: ['GET'])]
    public function checkRating(Request $request, Offre $offre): Response
    {
        try {
            $session = $request->getSession();
            $ratedOffers = $session->get('rated_offers', []);
            
            return $this->json([
                'hasRated' => in_array($offre->getId(), $ratedOffers),
                'currentRating' => $offre->getRating() ?? 0,
                'ratingCount' => $offre->getRatingCount() ?? 0
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la vérification'
            ], 500);
        }
    }
}