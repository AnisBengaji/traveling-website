<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Form\ReservationType1; // Ajoute ceci si ce n'est pas déjà fait
use App\Entity\User; // Ajouter cette ligne

// Ajoute cette ligne pour importer EvenementRepository
use App\Repository\EvenementRepository;

use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReservationController extends AbstractController
{
    #[Route('/reservations', name: 'reservation_index')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        // Récupère toutes les réservations
        $reservations = $reservationRepository->findAll();

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/reservation/{id}', name: 'reservation_show')]
    public function show(?Reservation $reservation): Response
    {
        if (!$reservation) {
            throw $this->createNotFoundException("Réservation introuvable.");
        }

        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }
    // Dans ton contrôleur (par exemple : ReservationController)

    #[Route('/new', name: 'reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        // 1. Création d'une nouvelle instance vide
        $reservation = new Reservation();
    
        // 2. Création du formulaire lié à l'objet Reservation
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
    
        // 3. Si formulaire soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer l'utilisateur avec id = 1 (l'utilisateur par défaut)
            $user = $em->getRepository(User::class)->find(1); // Chercher l'utilisateur avec id = 1
            if (!$user) {
                throw $this->createNotFoundException('Utilisateur non trouvé.');
            }
            
            // Affecter l'utilisateur à la réservation
            $reservation->setUser($user);
    
            // 5. Enregistrement en base
            $em->persist($reservation);
            $em->flush();
    
            // 6. Message flash + redirection
            $this->addFlash('success', 'Réservation créée avec succès.');
            return $this->redirectToRoute('reservation_index');
        }
    
        // 7. Affichage du formulaire
        return $this->render('reservation/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    

    

#[Route('/reservation/new/{id}', name: 'reservation_new_with_event')]
public function newWithEvent(
    Request $request,
    int $id,
    EntityManagerInterface $em,
    EvenementRepository $evenementRepository
): Response {
    // Récupérer l'événement par ID
    $evenement = $evenementRepository->find($id);

        if (!$evenement) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        // Créer une nouvelle réservation liée à l'événement
        $reservation = new Reservation();
        $reservation->setEvenement($evenement);
        $reservation->setPriceTotal($evenement->getPrice()); // Copier le prix de l'événement

        // L'utilisateur
        $user = $this->getUser();
        if (!$user) {
            $user = $em->getRepository(User::class)->find(1); // Pour les tests
        }

        // Créer le formulaire avec l'utilisateur et l'événement
        $form = $this->createForm(ReservationType1::class, $reservation, [
            'evenement' => $evenement,
            'user' => $user,
        ]);
        $form->handleRequest($request);

        // Traitement du formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            $reservation->setUser($user);

            // Vérifier si l'utilisateur a saisi des points à utiliser
            $walletPoints = $form->get('walletPoints')->getData();
            $wallet = $user->getWallet();

            if ($walletPoints > 0 && $wallet) {
                $score = $wallet->getScore();
                $price = $reservation->getPriceTotal();
                // Calculer la réduction possible (1 point = 1 €, ajustable)
                $maxDiscount = min($walletPoints, $score, $price); // Ne pas dépasser les points saisis, le score ou le prix

                if ($maxDiscount > 0) {
                    // Appliquer la réduction directement sur priceTotal
                    $reservation->setPriceTotal($price - $maxDiscount);
                    try {
                        $wallet->deductScore((int)$maxDiscount); // Déduire les points
                        $em->persist($wallet);
                        $this->addFlash('success', sprintf('Réduction de %d € appliquée grâce à votre Wallet.', $maxDiscount));
                    } catch (\Exception $e) {
                        $this->addFlash('warning', 'Score insuffisant pour appliquer une réduction.');
                    }
                } else {
                    $this->addFlash('warning', 'Montant de points invalide ou insuffisant.');
                }
            }

            // Enregistrer la réservation
            $em->persist($reservation);

            // Mettre à jour le Wallet
            if (!$wallet) {
                // Si l'utilisateur n'a pas encore de Wallet, on en crée un
                $wallet = new Wallet();
                $wallet->setUser($user);
                $wallet->setScore(0);
                $em->persist($wallet);
            }

            // Ajouter des points (100 points par réservation)
            $wallet->addScore(100);
            $em->persist($wallet);

            $em->flush(); // Enregistre tout en base

            $this->addFlash('success', 'Réservation créée avec succès. 100 points ajoutés à votre Wallet.');
            return $this->redirectToRoute('evenement_index');
        }

        return $this->render('reservation/new_with_event.html.twig', [
            'form' => $form->createView(),
            'evenement' => $evenement,
        ]);
    }

    #[Route('/reservation/{id}/edit', name: 'reservation_edit')]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $em): Response
    {
        // Création du formulaire
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        // Vérifie si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            // Rediriger vers la page de la réservation modifiée
            return $this->redirectToRoute('reservation_show', [
                'id' => $reservation->getId(),
            ]);
        }

        return $this->render('reservation/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reservation/{id}/delete', name: 'reservation_delete')]
    public function delete(Reservation $reservation, EntityManagerInterface $em): Response
    {
        // Supprime la réservation
        $em->remove($reservation);
        $em->flush();

        return $this->redirectToRoute('reservation_index');
    }

    
    
}