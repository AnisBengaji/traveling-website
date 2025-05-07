<?php

namespace App\Service;

use App\Entity\Offre;
use Psr\Log\LoggerInterface;

class MockSmsService implements SmsServiceInterface
{
    private LoggerInterface $logger;
    private NotificationTemplateService $templateService;

    public function __construct(
        LoggerInterface $logger,
        NotificationTemplateService $templateService
    ) {
        $this->logger = $logger;
        $this->templateService = $templateService;
    }

    public function sendTestSms(string $phoneNumber, string $message): void
    {
        // Validate phone number format
        if (!preg_match('/^\+[1-9]\d{1,14}$/', $phoneNumber)) {
            throw new \InvalidArgumentException('Invalid phone number format. Must start with + and contain 1-15 digits.');
        }

        // Log the mock SMS
        $this->logger->info('MOCK SMS sent successfully', [
            'to' => $phoneNumber,
            'message' => $message
        ]);
    }

    public function sendNewOfferNotification(Offre $offre): void
    {
        // Get the template
        $template = $this->templateService->getTemplate('new_offer');
        if (!$template) {
            throw new \RuntimeException('Template "new_offer" not found');
        }

        // Get SMS content
        $context = [
            'titre' => $offre->getTitre(),
            'description' => $offre->getDescription(),
            'destination' => $offre->getDestination(),
            'prix' => $offre->getPrix()
        ];

        $smsContent = $this->templateService->getSmsContent('new_offer', $context);
        if (!$smsContent) {
            throw new \RuntimeException('Failed to generate SMS content');
        }

        // Log the mock notification
        $this->logger->info('MOCK Offer notification SMS would be sent', [
            'offer_id' => $offre->getId(),
            'message' => $smsContent
        ]);
    }
} 