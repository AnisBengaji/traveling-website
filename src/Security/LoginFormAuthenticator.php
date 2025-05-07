<?php
namespace App\Security;

use App\Service\SessionBanManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    private SessionBanManager $banManager;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(SessionBanManager $banManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->banManager = $banManager;
        $this->urlGenerator = $urlGenerator;
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login');
    }

    public function supports(Request $request): bool
    {
        return $request->isMethod('POST') && $request->getPathInfo() === '/login';
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $csrfToken = $request->request->get('_csrf_token');

        if (empty($email) || empty($password)) {
            throw new CustomUserMessageAuthenticationException('Email and password cannot be empty');
        }

        if ($this->banManager->isBanned($email)) {
            $timeLeft = $this->banManager->getBanTimeLeft($email);
            throw new CustomUserMessageAuthenticationException(
                'Your account is temporarily locked. Try again in ' . ceil($timeLeft / 60) . ' minutes.'
            );
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [new CsrfTokenBadge('authenticate', $csrfToken)]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $roles = $token->getRoleNames();
        
        if (in_array('ROLE_ADMIN', $roles)) {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        }
        
        return new RedirectResponse($this->urlGenerator->generate('landing_index'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $email = $request->request->get('email');

        if ($email) {
            $this->banManager->addFailedAttempt($email);

            if ($this->banManager->isBanned($email)) {
                $request->getSession()->set('show_ban_message', true);
                return new RedirectResponse($this->urlGenerator->generate('app_login', [
                    'banned' => true,
                    'email' => $email
                ]));
            }

            $failedAttempts = $this->banManager->getFailedAttempts($email);
            $remainingAttempts = max(0, 3 - $failedAttempts);

            $request->getSession()->getFlashBag()->add(
                'error',
                sprintf('Invalid credentials. %d attempts remaining.', $remainingAttempts)
            );
        }

        return new RedirectResponse($this->getLoginUrl($request));
    }
}
