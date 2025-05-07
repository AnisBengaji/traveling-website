<?php

namespace App\Controller;

use App\Entity\Destination;
use App\Form\DestinationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
#[Route('/destination')]
final class DestinationController extends AbstractController
{
    private HttpClientInterface $client;
    private string $pixabayKey;

    public function __construct(HttpClientInterface $client, string $pixabayKey)
    {
        $this->client     = $client;
        $this->pixabayKey = $pixabayKey;
    }
    #[Route(name: 'app_destination_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $destinations = $entityManager
            ->getRepository(Destination::class)
            ->findAll();

        return $this->render('destination/index.html.twig', [
            'destinations' => $destinations,
        ]);
    }
    #[Route('/front', name: 'app_destination_front_index', methods: ['GET'])]
    public function frontIndex(EntityManagerInterface $em): Response
    {
        $destinations = $em->getRepository(Destination::class)->findAll();
        $images = [];
        foreach ($destinations as $dest) {
            $query    = urlencode($dest->getVille() . ' ' . $dest->getPays());
            $url      = "https://pixabay.com/api/?key={$this->pixabayKey}"
                      . "&q={$query}&per_page=3&image_type=photo";
            $response = $this->client->request('GET', $url);
            $data     = $response->toArray();
    
            $hits = $data['hits'] ?? [];
            $urls = [];
            for ($i = 0; $i < 3; $i++) {
                $urls[] = $hits[$i]['webformatURL'] 
                         ?? '/images/placeholder-city.jpg';
            }
    
            $images[$dest->getIdDestination()] = $urls;
        }
    
        return $this->render('destination/index-front.html.twig', [
            'destinations' => $destinations,
            'images'       => $images,
        ]);
    }
    #[Route('/front/filter', name: 'app_destination_front_filter', methods: ['GET'])]
    public function filter(Request $request, EntityManagerInterface $em): JsonResponse{
        $search = $request->query->get('q', '');
        $sort   = $request->query->get('sort', 'ville');
        $allowed = ['ville','pays','codePostal'];
        if (!in_array($sort, $allowed, true)) {
            $sort = 'ville';
        }
        $qb = $em->getRepository(Destination::class)
        ->createQueryBuilder('d');
        if ($search !== '') {
            $qb->andWhere('d.ville LIKE :s OR d.pays LIKE :s')
               ->setParameter('s', "%{$search}%");
        }
        $qb->orderBy("d.{$sort}", 'ASC');
        $qb->select('d.idDestination','d.ville','d.pays','d.codePostal','d.latitude','d.longitude');
        $results = $qb->getQuery()->getArrayResult();
        return $this->json($results);


    }


    #[Route('/new', name: 'app_destination_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $destination = new Destination();
        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($destination);
            $entityManager->flush();

            return $this->redirectToRoute('app_destination_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('destination/new.html.twig', [
            'destination' => $destination,
            'form' => $form,
        ]);
    }
     

    #[Route('/{idDestination}', name: 'app_destination_show', methods: ['GET'])]
    public function show(Destination $destination): Response
    {
        return $this->render('destination/show.html.twig', [
            'destination' => $destination,
        ]);
    }

    #[Route('/{idDestination}/edit', name: 'app_destination_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Destination $destination, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_destination_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('destination/edit.html.twig', [
            'destination' => $destination,
            'form' => $form,
        ]);
    }

    #[Route('/{idDestination}', name: 'app_destination_delete', methods: ['POST'])]
    public function delete(Request $request, Destination $destination, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$destination->getIdDestination(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($destination);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_destination_index', [], Response::HTTP_SEE_OTHER);
    }
}
 