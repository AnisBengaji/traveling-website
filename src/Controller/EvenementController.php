<?php

namespace App\Controller;

use App\Entity\Evenement;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;;
use Symfony\Component\HttpFoundation\ParameterBagInterface;
use App\Repository\UserRepository;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\WalletRepository;

#[Route('/evenement')]
class EvenementController extends AbstractController
{
    #[Route('/', name: 'evenement_index')]
    public function index(Request $request, EvenementRepository $evenementRepository, WalletRepository $walletRepository): Response
    {
        // Fetch the authenticated user
        $user = $this->getUser();
        $score = 0;

        // If user is authenticated, fetch their wallet
        if ($user) {
            $wallet = $walletRepository->findOneBy(['user' => $user]);
            if ($wallet) {
                $score = $wallet->getScore();
            }
        }

        // Fetch events with filters
        $search = $request->query->get('search', '');
        $prixMax = $request->query->get('prix_max', null);

        $queryBuilder = $evenementRepository->createQueryBuilder('e');

        if ($search) {
            $queryBuilder->andWhere('e.nom LIKE :search OR e.lieu LIKE :search')
                         ->setParameter('search', '%' . $search . '%');
        }

        if ($prixMax) {
            $queryBuilder->andWhere('e.price <= :prixMax')
                         ->setParameter('prixMax', $prixMax);
        }

        $evenements = $queryBuilder->getQuery()->getResult() ?? [];

        return $this->render('evenement/index.html.twig', [
            'evenements' => $evenements,
            'user' => $user,
            'score' => $score,
        ]);
    }

    #[Route('/admin', name: 'evenement_admin')]
    public function admin(Request $request, EvenementRepository $repository): Response
    {
        $search = $request->query->get('search');
        $prixMax = $request->query->get('prix_max');

        $queryBuilder = $repository->createQueryBuilder('e');

        if ($search) {
            $queryBuilder->andWhere('e.nom LIKE :search OR e.lieu LIKE :search')
                         ->setParameter('search', '%' . $search . '%');
        }

        if ($prixMax) {
            $queryBuilder->andWhere('e.price <= :prixMax')
                         ->setParameter('prixMax', $prixMax);
        }

        $evenements = $queryBuilder->getQuery()->getResult();

        return $this->render('evenement/index_admin.html.twig', [
            'evenements' => $evenements,
        ]);
    }

    #[Route('/new', name: 'evenement_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            if (empty($evenement->getDateEvenementDepart())) {
                $evenement->setDateEvenementDepart(new \DateTime());
            }

            if ($imageFile) {
                $filename = uniqid() . '.' . $imageFile->guessExtension();
                $uploadDirectory = $this->getParameter('images_directory');
                $imageFile->move($uploadDirectory, $filename);
                $evenement->setImageUrl('uploads/images/' . $filename);
            }

            $em->persist($evenement);
            $em->flush();

            return $this->redirectToRoute('evenement_admin');
        }

        return $this->render('evenement/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'evenement_show')]
    public function show(Evenement $evenement): Response
    {
        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    #[Route('/{id}/edit', name: 'evenement_edit')]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $filename = uniqid() . '.' . $imageFile->guessExtension();
                $uploadDirectory = $this->getParameter('images_directory');
                $imageFile->move($uploadDirectory, $filename);
                $evenement->setImageUrl('uploads/images/' . $filename);
            }

            $em->flush();

            return $this->redirectToRoute('evenement_admin');
        }

        return $this->render('evenement/edit.html.twig', [
            'form' => $form->createView(),
            'evenement' => $evenement,
        ]);
    }

    #[Route('/{id}/delete', name: 'evenement_delete')]
    public function delete(Evenement $evenement, EntityManagerInterface $em): Response
    {
        $em->remove($evenement);
        $em->flush();

        return $this->redirectToRoute('evenement_admin');
    }
}