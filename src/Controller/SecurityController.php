<?php
// src/Controller/SecurityController.php
namespace App\Controller;

use App\Service\SessionBanManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request, SessionBanManager $banManager): Response
{
    $error = $authenticationUtils->getLastAuthenticationError();
    $lastUsername = $authenticationUtils->getLastUsername() ?: $request->query->get('email', '');

    // Force check if ban has expired
    if ($lastUsername) {
        $banManager->isBanned($lastUsername); // This will clear if expired
    }

    $isBanned = $banManager->isBanned($lastUsername);
    $banTimeLeft = $banManager->getBanTimeLeft($lastUsername);
    $failedAttempts = $banManager->getFailedAttempts($lastUsername);
    $remainingAttempts = max(0, 3 - $failedAttempts);

    return $this->render('login.html.twig', [
        'last_username' => $lastUsername,
        'error' => $isBanned ? null : $error,
        'is_banned' => $isBanned,
        'ban_time_left' => $banTimeLeft,
        'remaining_attempts' => $remainingAttempts,
    ]);
}



    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/api/check-ban-status', name: 'app_check_ban_status', methods: ['GET'])]
    public function checkBanStatus(Request $request, SessionBanManager $banManager): JsonResponse
    {
        $email = $request->query->get('email');
        if (!$email) {
            return $this->json(['isBanned' => false]);
        }

        if ($banManager->isBanned($email)) {
            $banUntil = $banManager->getBanTimeLeft($email);
            return $this->json([
                'isBanned' => true,
                'message' => 'Your account is temporarily locked. Please try again after '.ceil($banUntil/60).' minutes.',
                'secondsLeft' => $banUntil
            ]);
        }

        return $this->json(['isBanned' => false]);
    }
}