<?php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ForgotPasswordController extends AbstractController
{
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function request(Request $request, MailerInterface $mailer, SessionInterface $session): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user) {
                // Generate a token
                $resetToken = bin2hex(random_bytes(32));
                $session->set('reset_token', $resetToken);
                $session->set('reset_user_id', $user->getId());
                $session->set('reset_token_expires', (new \DateTime('+1 hour'))->getTimestamp());

                // Send email with reset link
                $resetUrl = $this->generateUrl('app_reset_password', ['token' => $resetToken], UrlGeneratorInterface::ABSOLUTE_URL);

                $emailMessage = (new Email())
                    ->from('noreply@yourdomain.com')
                    ->to($email)
                    ->subject('Password Reset Request')
                    ->html("<p>To reset your password, please click <a href='{$resetUrl}'>here</a>.</p>");

                $mailer->send($emailMessage);

                $this->addFlash('success', 'Password reset link sent to your email.');
                return $this->redirectToRoute('app_login');
            } else {
                $this->addFlash('error', 'No user found with that email address.');
            }
        }

        return $this->render('reset/request.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(Request $request, SessionInterface $session, string $token): Response
    {
        // Retrieve the session data
        $savedToken = $session->get('reset_token');
        $savedUserId = $session->get('reset_user_id');
        $expiresAt = $session->get('reset_token_expires');

        // Validate token and expiration
        if (!$savedToken || !$savedUserId || !$expiresAt || $token !== $savedToken || time() > $expiresAt) {
            $this->addFlash('error', 'Invalid or expired reset token.');
            return $this->redirectToRoute('app_forgot_password');  // Redirect to forgot password page
        }

        // Retrieve user based on session data
        $user = $this->entityManager->getRepository(User::class)->find($savedUserId);

        if (!$user) {
            $this->addFlash('error', 'No user found.');
            return $this->redirectToRoute('app_forgot_password');
        }

        // Handle the password reset after form submission
        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');

            // Hash and save the new password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Remove session data
            $session->remove('reset_token');
            $session->remove('reset_user_id');
            $session->remove('reset_token_expires');

            $this->addFlash('success', 'Password reset successfully.');
            return $this->redirectToRoute('app_login'); // Redirect to login page
        }

        // If the token is valid, show the reset password form
        return $this->render('reset/reset.html.twig', ['token' => $token]);
    }
}
