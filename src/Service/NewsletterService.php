<?php

namespace App\Service;

use App\Entity\Offre;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NewsletterService
{
    private MailerInterface $mailer;
    private UserRepository $userRepository;
    private NotificationTemplateService $templateService;

    public function __construct(
        MailerInterface $mailer,
        UserRepository $userRepository,
        NotificationTemplateService $templateService
    ) {
        $this->mailer = $mailer;
        $this->userRepository = $userRepository;
        $this->templateService = $templateService;
    }

    public function sendNewOfferNotification(Offre $offre): void
    {
        $subscribedUsers = $this->userRepository->findSubscribedUsers();
        $template = $this->templateService->getTemplate('new_offer');

        if (!$template) {
            throw new \RuntimeException('Template "new_offer" not found');
        }

        foreach ($subscribedUsers as $user) {
            $context = [
                'titre' => $offre->getTitre(),
                'description' => $offre->getDescription(),
                'destination' => $offre->getDestination(),
                'prix' => $offre->getPrix(),
                'image' => $offre->getImage(),
                'user_email' => $user->getEmail()
            ];

            $emailContent = $this->templateService->getEmailContent('new_offer', $context);

            if (!$emailContent) {
                continue;
            }

            $email = (new Email())
                ->from('noreply@yourdomain.com')
                ->to($user->getEmail())
                ->subject('Nouvelle offre disponible !')
                ->html($emailContent);

            $this->mailer->send($email);
        }
    }
} 