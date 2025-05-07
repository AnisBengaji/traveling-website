<?php
// src/Controller/TutorialController.php
namespace App\Controller;

use App\Entity\Tutorial;
use App\Form\TutorialType;
use App\Repository\TutorialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Offre;

#[Route('/tutorial')]
class TutorialController extends AbstractController
{
    #[Route('/', name: 'app_tutorial_index', methods: ['GET', 'POST'])]
    public function index(TutorialRepository $tutorialRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $tutorial = new Tutorial();
        $form = $this->createForm(TutorialType::class, $tutorial);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tutorial);
            $entityManager->flush();
            $this->addFlash('success', 'Le tutoriel a été créé avec succès.');
            return $this->redirectToRoute('app_tutorial_index');
        }

        return $this->render('tutorial/index.html.twig', [
            'tutorials' => $tutorialRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'app_tutorial_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tutorial = new Tutorial();
        $form = $this->createForm(TutorialType::class, $tutorial);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Vérification des dates
                if ($tutorial->getDateDebut() === null || $tutorial->getDateFin() === null) {
                    $this->addFlash('error', 'Les dates de début et de fin sont obligatoires.');
                } elseif ($tutorial->getDateFin() < $tutorial->getDateDebut()) {
                    $this->addFlash('error', 'La date de fin ne peut pas être antérieure à la date de début.');
                } else {
                    try {
                        $entityManager->persist($tutorial);
                        $entityManager->flush();
                        $this->addFlash('success', 'Tutoriel créé avec succès.');
                        return $this->redirectToRoute('app_tutorial_index');
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors de la création du tutoriel.');
                    }
                }
            } else {
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
            }
        }

        return $this->render('tutorial/new.html.twig', [
            'tutorial' => $tutorial,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_tutorial_show', methods: ['GET'])]
    public function show(?Tutorial $tutorial): Response
    {
        if (!$tutorial) {
            throw $this->createNotFoundException('Tutoriel non trouvé');
        }

        return $this->render('tutorial/show.html.twig', [
            'tutorial' => $tutorial,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tutorial_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?Tutorial $tutorial, EntityManagerInterface $entityManager): Response
    {
        if (!$tutorial) {
            throw $this->createNotFoundException('Tutoriel non trouvé');
        }

        $form = $this->createForm(TutorialType::class, $tutorial);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Vérification des dates lors de la modification
                if ($tutorial->getDateDebut() === null || $tutorial->getDateFin() === null) {
                    $this->addFlash('error', 'Les dates de début et de fin sont obligatoires.');
                } elseif ($tutorial->getDateFin() < $tutorial->getDateDebut()) {
                    $this->addFlash('error', 'La date de fin ne peut pas être antérieure à la date de début.');
                } else {
                    try {
                        $entityManager->flush();
                        $this->addFlash('success', 'Tutoriel modifié avec succès.');
                        return $this->redirectToRoute('app_tutorial_index');
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors de la modification.');
                    }
                }
            } else {
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
            }
        }

        return $this->render('tutorial/edit.html.twig', [
            'tutorial' => $tutorial,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_tutorial_delete', methods: ['POST'])]
    public function delete(Request $request, ?Tutorial $tutorial, EntityManagerInterface $entityManager): Response
    {
        if (!$tutorial) {
            throw $this->createNotFoundException('Tutoriel non trouvé');
        }

        if ($this->isCsrfTokenValid('delete'.$tutorial->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($tutorial);
                $entityManager->flush();
                $this->addFlash('success', 'Tutoriel supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_tutorial_index');
    }

    #[Route('/admin/offre/{id}/tutorials', name: 'app_tutorials_by_offre')]
    public function tutorialsByOffre(Offre $offre): Response
    {
        $tutorials = $offre->getTutorials();

        return $this->render('admin/tutorials_by_offre.html.twig', [
            'offre' => $offre,
            'tutorials' => $tutorials,
        ]);
    }
}