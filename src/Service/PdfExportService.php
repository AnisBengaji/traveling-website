<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PdfExportService
{
    private $twig;
    private $params;

    public function __construct(Environment $twig, ParameterBagInterface $params)
    {
        $this->twig = $twig;
        $this->params = $params;
    }

    public function generatePdf(string $html, array $options = []): Response
    {
        try {
            // Configuration de Dompdf
            $pdfOptions = new Options();
            $pdfOptions->set('defaultFont', 'Arial');
            $pdfOptions->set('isHtml5ParserEnabled', true);
            $pdfOptions->set('isPhpEnabled', true);
            $pdfOptions->set('isRemoteEnabled', true);
            $pdfOptions->set('chroot', $this->params->get('kernel.project_dir'));
            
            // Options personnalisées
            if (isset($options['orientation'])) {
                $pdfOptions->set('defaultPaperOrientation', $options['orientation']);
            }
            if (isset($options['size'])) {
                $pdfOptions->set('defaultPaperSize', $options['size']);
            }
            if (isset($options['dpi'])) {
                $pdfOptions->set('dpi', $options['dpi']);
            }

            // Initialisation de Dompdf
            $dompdf = new Dompdf($pdfOptions);
            $dompdf->loadHtml($html);
            
            // Rendu du PDF
            $dompdf->render();

            // Ajout des métadonnées
            $dompdf->addInfo('Title', $options['title'] ?? 'Document PDF');
            $dompdf->addInfo('Author', $options['author'] ?? 'WebTripina');
            $dompdf->addInfo('Subject', $options['subject'] ?? 'Export des données');
            $dompdf->addInfo('Keywords', $options['keywords'] ?? 'export, pdf, offres, tutoriels');
            $dompdf->addInfo('Creator', 'WebTripina PDF Generator');
            $dompdf->addInfo('CreationDate', date('Y-m-d H:i:s'));

            // Génération de la réponse
            $output = $dompdf->output();
            
            return new Response(
                $output,
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => sprintf(
                        'attachment; filename="%s.pdf"',
                        $options['filename'] ?? 'export_' . date('Y-m-d_H-i-s')
                    ),
                    'Content-Length' => strlen($output),
                    'Cache-Control' => 'private, max-age=0, must-revalidate',
                    'Pragma' => 'public'
                ]
            );
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf(
                'Erreur lors de la génération du PDF : %s',
                $e->getMessage()
            ));
        }
    }

    public function generateOffresPdf(array $offres, array $tutorials, array $options = []): Response
    {
        try {
            $html = $this->twig->render('admin/export_offres_pdf.html.twig', [
                'offres' => $offres,
                'tutorials' => $tutorials,
            ]);

            return $this->generatePdf($html, array_merge([
                'title' => 'Export des Offres et Tutoriels',
                'filename' => 'export_offres_tutoriels',
                'orientation' => 'portrait',
                'size' => 'A4',
                'dpi' => 300,
            ], $options));
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf(
                'Erreur lors de la génération du PDF des offres : %s',
                $e->getMessage()
            ));
        }
    }
} 