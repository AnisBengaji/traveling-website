<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class EmailController extends AbstractController
{
    #[Route('/reservation/{id}/send-email', name: 'send_email_reservation')]
    public function sendEmail(
        int $id,
        ReservationRepository $reservationRepository,
        MailerInterface $mailer
    ): Response {
        // Récupérer la réservation
        $reservation = $reservationRepository->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable');
        }

        // Récupérer l'utilisateur associé à la réservation
        $user = $reservation->getUser();

        if (!$user || !$user->getEmail()) {
            throw $this->createNotFoundException('Utilisateur ou email introuvable pour cette réservation');
        }

        // Créer l'email
        $email = (new TemplatedEmail())
            ->from('khaledabed930@gmail.com') // Ton adresse email
            ->to($user->getEmail()) // Email de l'utilisateur
            ->subject('Confirmation de votre réservation')
            ->htmlTemplate('email/send_email.html.twig') // Template HTML
            ->context([
                'reservation' => $reservation, // Passer la réservation à Twig
                'user' => $user, // Passer l'utilisateur à Twig
            ]);

        // Envoyer l'email
        $mailer->send($email);

       // Ajouter un message flash

       return new JsonResponse(['success' => 'Email envoyé avec succès']);

    }
}