<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class WalletController extends AbstractController
{
    #[Route('/wallet', name: 'wallet_index')]
    public function index(EntityManagerInterface $em): Response
    {
        // Récupérer l'utilisateur avec ID 1
        $user = $em->getRepository(User::class)->find(1);

        if (!$user) {
            throw $this->createNotFoundException("Utilisateur non trouvé");
        }

        $wallet = $user->getWallet();
        $score = $wallet ? $wallet->getScore() : 0;

        return $this->render('wallet/index.html.twig', [
            'user' => $user,
            'score' => $score,
        ]);
    }
}