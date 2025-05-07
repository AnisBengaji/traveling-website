<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class SessionBanManager
{
    private RequestStack $requestStack;
    private int $maxAttempts;
    private int $banDuration;

    public function __construct(RequestStack $requestStack, int $maxAttempts, int $banDuration)
    {
        $this->requestStack = $requestStack;
        $this->maxAttempts = $maxAttempts;
        $this->banDuration = $banDuration;
    }

    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    public function addFailedAttempt(string $email): void
    {
        $session = $this->getSession();
        $attempts = $session->get('login_attempts', []);

        // Reset if last attempt was too long ago
        if (isset($attempts[$email]['last_attempt']) && 
            (time() - $attempts[$email]['last_attempt'] > $this->banDuration)) {
            unset($attempts[$email]);
        }

        $attempts[$email] = [
            'count' => ($attempts[$email]['count'] ?? 0) + 1,
            'last_attempt' => time()
        ];

        $session->set('login_attempts', $attempts);

        // Ban if too many attempts
        if ($attempts[$email]['count'] >= $this->maxAttempts) {
            $this->banUser($email);
        }
    }

    public function getFailedAttempts(string $email): int
    {
        $attempts = $this->getSession()->get('login_attempts', []);
        return $attempts[$email]['count'] ?? 0;
    }

   
    public function banUser(string $email): void
    {
        $session = $this->getSession();
        $bans = $session->get('bans', []);
        $banUntil = time() + $this->banDuration; // Use the configured duration
        $bans[$email] = $banUntil;
        $session->set('bans', $bans);
        $session->save();
    }

public function isBanned(string $email): bool
{
    $session = $this->getSession();
    $bans = $session->get('bans', []);

    if (isset($bans[$email])) {
        if (time() < $bans[$email]) {
            return true; // Still banned
        } else {
            // Ban expired - clear everything
            $this->clearBan($email);
            $session->save(); // Force save
            return false;
        }
    }

    return false;
}


    public function getBanTimeLeft(string $email): int
    {
        $session = $this->getSession();
        $bans = $session->get('bans', []);

        if (!isset($bans[$email])) {
            return 0;
        }

        return max(0, $bans[$email] - time());
    }

    public function clearBan(string $email): void
    {
        $session = $this->getSession();

        $bans = $session->get('bans', []);
        unset($bans[$email]);
        $session->set('bans', $bans);

        $attempts = $session->get('login_attempts', []);
        unset($attempts[$email]);
        $session->set('login_attempts', $attempts);
    }
}
