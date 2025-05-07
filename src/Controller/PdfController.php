<?php

namespace App\Controller;

use App\Entity\Reservation;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfController extends AbstractController
{
    #[Route('/reservation/{id}/pdf', name: 'generate_reservation_pdf')]
    public function generateReservationPdf(Reservation $reservation): Response
    {
        // Générer le contenu du QR code avec les détails de la réservation
        $qrCodeContent = $this->generateQrCodeContent($reservation);

        // Créer le QR code avec le contenu sous forme de texte
        $qrCodeResult = Builder::create()
            ->writer(new SvgWriter()) // Utiliser SVG pour éviter GD
            ->data($qrCodeContent)
            ->size(300)
            ->margin(10)
            ->build();

        // Récupérer le QR code au format Data URI pour l'inclure dans le PDF
        $qrCodeDataUri = $qrCodeResult->getDataUri();

        // Préparer le HTML pour le PDF
        $html = $this->renderView('pdf/reservation_details.html.twig', [
            'reservation' => $reservation,
            'qrCode' => $qrCodeDataUri,
        ]);

        // Configurer Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($pdfOptions);

        // Charger le HTML dans Dompdf
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Retourner le PDF au navigateur
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="reservation_'.$reservation->getId().'.pdf"',
            ]
        );
    }

    private function generateQrCodeContent(Reservation $reservation): string
    {
        $id = $reservation->getId();
        $event = $reservation->getEvenement();
        $eventName = $event ? $event->getNom() : 'Inconnu';
        $lieu = $event ? $event->getLieu() : 'Non spécifié';
        $dateDepart = $event && $event->getDateEvenementDepart() ? $event->getDateEvenementDepart()->format('d/m/Y') : 'N/A';

        $generatedAt = (new \DateTime())->format('d/m/Y H:i');
        $checksum = strtoupper(substr(hash('sha256', 'RSV-' . $id), 0, 12));

        // Contenu à afficher dans le QR code
        return <<<TEXT
--- Détails Réservation ---
ID Réservation  : $id
Événement       : $eventName
Lieu            : $lieu
Date de départ  : $dateDepart

Généré le       : $generatedAt
Code de vérif.  : $checksum
----------------------------
TEXT;
    }
}