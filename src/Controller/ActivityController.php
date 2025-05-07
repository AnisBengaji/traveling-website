<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Destination;
use App\Form\ActivityType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/activity')]
class ActivityController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private string $weatherApiKey;

    public function __construct(HttpClientInterface $httpClient, string $weatherApiKey)
    {
        $this->httpClient = $httpClient;
        $this->weatherApiKey = $weatherApiKey;
    }

    #[Route(name: 'app_activity_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $activities = $entityManager->getRepository(Activity::class)->findAll();

        return $this->render('activity/index.html.twig', [
            'activities' => $activities,
        ]);
    }

    #[Route('/alt/{idDestination}', name: 'app_activity_index2', methods: ['GET'])]
    public function index2(Destination $destination): Response
    {
        $activities = $destination->getActivities();    

        return $this->render('activity/index2.html.twig', [
            'activities' => $activities,
            'destination' => $destination,
        ]);
    }

    #[Route('/events', name: 'app_activity_events', methods: ['GET'])]
    public function events(EntityManagerInterface $em): JsonResponse
    {
        $activities = $em->getRepository(Activity::class)->findAll();

        $events = array_map(fn(Activity $a) => [
            'id'    => $a->getIdActivity(),
            'title' => $a->getNomActivity(),
            'start' => $a->getDateActivite()?->format('Y-m-d'),
            'url'   => $this->generateUrl('app_activity_show2', [
                'idActivity' => $a->getIdActivity()
            ]),
        ], $activities);

        return $this->json($events);
    }

    #[Route('/calendar', name: 'app_activity_calendar')]
    public function calendar(): Response
    {
        return $this->render('activity/calendar.html.twig');
    }

    #[Route('/new', name: 'app_activity_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $activity = new Activity();
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach (['imageActivity', 'imageActivity2', 'imageActivity3'] as $field) {
                $imageFile = $form->get($field)->getData();
                if ($imageFile) {
                    $newFilename = $this->uploadFile($imageFile);
                    $setter = 'set' . ucfirst($field);
                    $activity->$setter($newFilename);
                }
            }

            $entityManager->persist($activity);
            $entityManager->flush();

            return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activity/new.html.twig', [
            'activity' => $activity,
            'form' => $form,
        ]);
    }

    #[Route('/activity/{idActivity}', name: 'app_activity_show', methods: ['GET'])]
    public function show($idActivity, EntityManagerInterface $entityManager): Response
    {
        // Fetch the Activity entity manually
        $activity = $entityManager->getRepository(Activity::class)->find($idActivity);

        if (!$activity) {
            throw $this->createNotFoundException('Activity not found');
        }

        return $this->render('activity/show.html.twig', [
            'activity' => $activity,
        ]);
    }

    #[Route('/alt/activity/{idActivity}', name: 'app_activity_show2', methods: ['GET'])]
    public function show2($idActivity, EntityManagerInterface $entityManager): Response
    {
        // Fetch the Activity entity manually
        $activity = $entityManager->getRepository(Activity::class)->find($idActivity);

        if (!$activity) {
            throw $this->createNotFoundException('Activity not found');
        }

        $qrContent = sprintf(
            "Nom: %s\nType: %s\nPrix: %.2f DT\nDate: %s",
            $activity->getNomActivity(),
            $activity->getType(),
            $activity->getActivityPrice(),
            $activity->getDateActivite()?->format('d/m/Y') ?? 'N/A'
        );

        $qrCode = Builder::create()
            ->writer(new SvgWriter())
            ->data($qrContent)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(6)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->build();

        $qrCodeSvg = $qrCode->getString();

        return $this->render('activity/show2.html.twig', [
            'activity' => $activity,
            'qrCodeSvg' => $qrCodeSvg,
        ]);
    }

    #[Route('/{idActivity}/edit', name: 'app_activity_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Activity $activity, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach (['imageActivity', 'imageActivity2', 'imageActivity3'] as $field) {
                $imageFile = $form->get($field)->getData();
                if ($imageFile) {
                    $newFilename = $this->uploadFile($imageFile);
                    $setter = 'set' . ucfirst($field);
                    $activity->$setter($newFilename);
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activity/edit.html.twig', [
            'activity' => $activity,
            'form' => $form,
        ]);
    }

    #[Route('/{idActivity}', name: 'app_activity_delete', methods: ['POST'])]
    public function delete(Request $request, Activity $activity, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $activity->getIdActivity(), $request->request->get('_token'))) {
            $entityManager->remove($activity);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/weather/{city}', name: 'app_weather', methods: ['GET'])]
    public function getWeather(string $city): Response
    {
        $url = sprintf(
            'http://api.weatherapi.com/v1/current.json?key=%s&q=%s&lang=fr',
            $this->weatherApiKey,
            urlencode($city)
        );
    
        try {
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();
    
            return $this->render('activity/weather.html.twig', [
                'city' => $data['location']['name'],
                'country' => $data['location']['country'],
                'temperature' => $data['current']['temp_c'],
                'description' => $data['current']['condition']['text'],
                'icon' => $data['current']['condition']['icon'],
            ]);
        } catch (\Exception $e) {
            return $this->render('activity/weather.html.twig', [
                'city' => $city,
                'country' => 'N/A',
                'temperature' => 'N/A',
                'description' => 'Données indisponibles',
                'icon' => '',
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function uploadFile($file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = preg_replace('/[^a-zA-Z0-9_]/', '_', $originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($this->getParameter('uploads_directory'), $newFilename);
        } catch (FileException $e) {
            throw new \Exception('Échec du téléversement du fichier.');
        }

        return $newFilename;
    }

    #[Route('/export/excel', name: 'app_activity_export_excel', methods: ['GET'])]
    public function exportToExcel(EntityManagerInterface $entityManager): StreamedResponse
{
    $activities = $entityManager->getRepository(Activity::class)->findAll();

   
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

   
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Nom');
    $sheet->setCellValue('C1', 'Type');
    $sheet->setCellValue('D1', 'Prix');
    $sheet->setCellValue('E1', 'Date');

    
    $row = 2; 
    foreach ($activities as $activity) {
        $sheet->setCellValue('A' . $row, $activity->getIdActivity());
        $sheet->setCellValue('B' . $row, $activity->getNomActivity());
        $sheet->setCellValue('C' . $row, $activity->getType());
        $sheet->setCellValue('D' . $row, $activity->getActivityPrice());
        $sheet->setCellValue('E' . $row, $activity->getDateActivite()?->format('Y-m-d'));
        $row++;
    }

    $response = new StreamedResponse(function () use ($spreadsheet) {
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    });

    
    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->headers->set('Content-Disposition', ResponseHeaderBag::DISPOSITION_ATTACHMENT . ';filename="activities.xlsx"');

    return $response;
}
}