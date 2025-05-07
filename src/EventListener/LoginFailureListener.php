<?php
// src/EventListener/LoginFailureListener.php
namespace App\EventListener;

use App\Service\SessionBanManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class LoginFailureListener implements EventSubscriberInterface
{
    public function __construct(
        private SessionBanManager $banManager,
        private RequestStack $requestStack
    ) {}

    public function onLoginFailure(LoginFailureEvent $event): void
{
    try {
        $passport = $event->getPassport();
        if (!$passport || !$passport->hasBadge(UserBadge::class)) {
            return;
        }

        $userBadge = $passport->getBadge(UserBadge::class);
        $email = $userBadge->getUserIdentifier();
        
        $this->banManager->addFailedAttempt($email);
        
        $failedAttempts = $this->banManager->getFailedAttempts($email);
        
        if ($failedAttempts >= 3) {
            // Use the banManager's configured duration instead of hardcoding
            $this->banManager->banUser($email);
            $this->requestStack->getSession()->set('show_ban_message', true);
        }
    } catch (\Exception $e) {
        error_log("LoginFailureListener error: ".$e->getMessage());
    }
}
    public static function getSubscribedEvents(): array
    {
        return [LoginFailureEvent::class => 'onLoginFailure'];
    }
}