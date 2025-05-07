<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; // Correct for Symfony 5.3+
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CustomResetPasswordService
{ 
     private $session;
    private $passwordHasher;
    private $resetPasswordHelper;
    private $entityManager;


    public function __construct(
        SessionInterface $session,
        PasswordHasherInterface $passwordHasher,  // Ensure this is correct
        ResetPasswordHelperInterface $resetPasswordHelper,
        EntityManagerInterface $entityManager
    ) {
        $this->session = $session;
        $this->passwordHasher = $passwordHasher;
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->entityManager = $entityManager;
    }

    public function createResetToken(User $user): string
    {
        $token = bin2hex(random_bytes(32));

        $this->session->set('reset_token', $token);
        $this->session->set('reset_user_email', $user->getEmail());

        return $token;
    }

    public function resetPassword(string $token, string $newPassword): void
    {
        if ($token !== $this->session->get('reset_token')) {
            throw new \Exception('Invalid or expired reset token.');
        }

        $userEmail = $this->session->get('reset_user_email');

        if (!$userEmail) {
            throw new \Exception('No user found for reset.');
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);

        if (!$user) {
            throw new \Exception('User not found.');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->flush();

        // Clear reset data from session after successful reset
        $this->session->remove('reset_token');
        $this->session->remove('reset_user_email');
    }
}
