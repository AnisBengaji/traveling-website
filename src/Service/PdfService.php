<?php

namespace App\Service;

use Knp\Snappy\Pdf;
use Twig\Environment;

class PdfService
{
    private Pdf $pdf;
    private Environment $twig;

    public function __construct(Pdf $pdf, Environment $twig)
    {
        $this->pdf = $pdf;
        $this->twig = $twig;
    }

    public function generateOfferPdf($offer): string
    {
        $html = $this->twig->render('pdf/offer_details.html.twig', [
            'offer' => $offer
        ]);

        return $this->pdf->getOutputFromHtml($html);
    }

    public function generateOffersListPdf($offers): string
    {
        $html = $this->twig->render('pdf/offers_list.html.twig', [
            'offers' => $offers
        ]);

        return $this->pdf->getOutputFromHtml($html);
    }
} 