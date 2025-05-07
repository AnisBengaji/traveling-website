<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer,
        LoggerInterface $logger
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $logger->debug('Form submitted', [
                'valid' => $form->isValid(),
                'errors' => $form->getErrors(true, true)->__toString(),
                'data' => $form->getData()
            ]);

            // Server-side PHP validation
            $nom = $form->get('nom')->getData();
            $prenom = $form->get('prenom')->getData();
            $numTel = $form->get('numTel')->getData();
            $email = $form->get('email')->getData();
            $mdp = $form->get('mdp')->getData();
            $role = $form->get('role')->getData();

            // Check if email already exists first
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $form->get('email')->addError(new FormError('This email is already registered.'));
                $logger->debug('Email already exists', ['email' => $email]);
                $this->addFlash('error', 'This email is already registered.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            // Validate nom (required, alphabetic, 2-50 chars)
            if (empty($nom)) {
                $form->get('nom')->addError(new FormError('First name is required.'));
            } elseif (!preg_match('/^[a-zA-Z\s]{2,50}$/', $nom)) {
                $form->get('nom')->addError(new FormError('First name must be 2-50 alphabetic characters.'));
            }

            // Validate prenom (required, alphabetic, 2-50 chars)
            if (empty($prenom)) {
                $form->get('prenom')->addError(new FormError('Last name is required.'));
            } elseif (!preg_match('/^[a-zA-Z\s]{2,50}$/', $prenom)) {
                $form->get('prenom')->addError(new FormError('Last name must be 2-50 alphabetic characters.'));
            }

            // Validate numTel (required, numeric, 8-15 digits)
            if (empty($numTel)) {
                $form->get('numTel')->addError(new FormError('Phone number is required.'));
            } elseif (!preg_match('/^[0-9]{8,15}$/', $numTel)) {
                $form->get('numTel')->addError(new FormError('Phone number must be 8-15 digits.'));
            }

            // Validate email (required, valid format)
            if (empty($email)) {
                $form->get('email')->addError(new FormError('Email is required.'));
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $form->get('email')->addError(new FormError('Please enter a valid email address.'));
            }

            // Validate mdp (required, min 8 chars, 1 uppercase, 1 lowercase, 1 digit)
            if (empty($mdp)) {
                $form->get('mdp')->addError(new FormError('Password is required.'));
            } elseif (strlen($mdp) < 8 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $mdp)) {
                $form->get('mdp')->addError(new FormError('Password must be at least 8 characters, with 1 uppercase, 1 lowercase, and 1 digit.'));
            }

            // Validate role (required, valid option)
            $validRoles = ['client', 'fournisseur', 'admin'];
            if (empty($role)) {
                $form->get('role')->addError(new FormError('Role is required.'));
            } elseif (!in_array($role, $validRoles)) {
                $form->get('role')->addError(new FormError('Please select a valid role.'));
            }

            // Log all form errors for debugging
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            if (!empty($errors)) {
                $logger->debug('Form validation errors', ['errors' => $errors]);
                $this->addFlash('error', 'Form validation failed. Please check your input.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            // Hash le mot de passe et le stocke dans la propriété 'mdp'
            $hashedPassword = $passwordHasher->hashPassword($user, $mdp);
            $user->setMdp($hashedPassword);

            // Récupération du rôle sélectionné
            $user->setRole($role);

            // Enregistrement dans la base de données
            try {
                $entityManager->persist($user);
                $entityManager->flush();
                $logger->debug('User registered', ['email' => $user->getEmail()]);
            } catch (UniqueConstraintViolationException $e) {
                $form->get('email')->addError(new FormError('This email is already registered.'));
                $logger->error('Duplicate email detected', ['email' => $user->getEmail(), 'error' => $e->getMessage()]);
                $this->addFlash('error', 'This email is already registered.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            // Envoi de l'email de bienvenue
            try {
                $renderedTemplate = $this->renderView('emails/welcome.html.twig', ['user' => $user]);
                $logger->debug('Template rendered', ['template' => 'emails/welcome.html.twig']);
                $plainText = sprintf(
                    "Dear %s %s,\n\nThank you for joining Trip In. We’re thrilled to have you in our travel community.\n\nLog in to start exploring destinations: https://tripin.com/login\n\nFor questions, contact support@tripin.com.\n\n© %s Trip In. All rights reserved.",
                    $user->getNom() ?? 'User',
                    $user->getPrenom() ?? '',
                    date('Y')
                );
                $email = (new Email())
                    ->from('linatekaya00@gmail.com')
                    ->to($user->getEmail())
                    ->subject('Welcome to Trip In!')
                    ->html($renderedTemplate)
                    ->text($plainText);
                $logger->debug('Sending email', ['to' => $user->getEmail(), 'from' => 'linatekaya00@gmail.com']);
                $mailer->send($email);
                $logger->info('Welcome email sent', ['email' => $user->getEmail()]);
            } catch (\Exception $e) {
                $logger->error('Failed to send welcome email', [
                    'error' => $e->getMessage(),
                    'email' => $user->getEmail()
                ]);
                $this->addFlash('error', 'Registration successful, but unable to send welcome email: ' . $e->getMessage());
            }

           
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/test-email', name: 'test_email')]
    public function testEmail(MailerInterface $mailer, LoggerInterface $logger): Response
    {
        try {
            $email = (new Email())
                ->from('linatekaya00@gmail.com')
                ->to('enaalolo123@gmail.com')
                ->subject('Test Email from Trip In')
                ->html('<p>This is a test email from Trip In.</p>')
                ->text('This is a test email from Trip In.');
            $logger->debug('Sending test email', ['to' => 'enaalolo123@gmail.com']);
            $mailer->send($email);
            $logger->info('Test email sent');
            $this->addFlash('success', 'Test email sent successfully!');
        } catch (\Exception $e) {
            $logger->error('Test email failed', ['error' => $e->getMessage()]);
            $this->addFlash('error', 'Failed to send test email: ' . $e->getMessage());
        }
        return $this->redirectToRoute('app_login');
    }
}