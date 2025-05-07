<?php


// src/Controller/SmsController.php
// src/Controller/SmsController.php
// src/Controller/SmsController.php

namespace App\Controller;

use App\Entity\Reservation;
use App\Service\TwilioService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

// src/Controller/SmsController.php

namespace App\Controller;

use App\Service\TwilioService;
use App\Entity\Reservation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class SmsController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/reservation/{id}/send_sms', name: 'send_sms')]
    public function sendSms(int $id, TwilioService $twilioService): JsonResponse
    {
        // Récupérer la réservation depuis la base de données
        $reservation = $this->entityManager->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        // Récupérer l'événement associé à la réservation
        $event = $reservation->getEvenement();
        $eventName = $event ? $event->getNom() : 'Inconnu';
        $lieu = $event ? $event->getLieu() : 'Non spécifié';
        $dateDepart = $event && $event->getDateEvenementDepart() ? $event->getDateEvenementDepart()->format('d/m/Y') : 'N/A';

        // Récupérer le numéro de téléphone de l'utilisateur
        $user = $reservation->getUser();
        $phoneNumber = $user->getNumero();

        // Ajouter le code pays si nécessaire
        if (substr($phoneNumber, 0, 1) !== '+') {
            $phoneNumber = '+216' . $phoneNumber; // Ajout automatique du préfixe pour la Tunisie
        }

        // Générer un code de vérification unique
        $generatedAt = (new \DateTime())->format('d/m/Y H:i');
        $checksum = strtoupper(substr(hash('sha256', 'RSV-' . $id), 0, 12));
        $thankYouMessage = "\n\nMerci de choisir notre agence 2025 TRIPPIN  !";


        // Message formaté avec les détails de la réservation
        $message = <<<TEXT
--- Détails Réservation ---
ID Réservation  : $id
Événement       : $eventName
Lieu            : $lieu
Date de départ  : $dateDepart

Généré le       : $generatedAt
Code de vérif.  : $checksum
----------------------------
$thankYouMessage

TEXT;

        // Envoyer le SMS via Twilio
        $smsSid = $twilioService->sendSms($phoneNumber, $message);

        // Retourner une réponse JSON pour indiquer que le SMS a été envoyé
        return new JsonResponse(['success' => true, 'message' => 'SMS envoyé avec succès.']);
    }
}