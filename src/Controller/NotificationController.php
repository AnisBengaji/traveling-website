<?php

namespace App\Controller;

use App\Service\SmsService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    #[Route('/send-sms', name: 'send_sms', methods: ['POST'])]
    public function sendSMS(Request $request, SmsService $smsService, ?LoggerInterface $logger = null): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $logger?->warning('No authenticated user found for SMS notification');
            return new Response('No authenticated user found.', 401);
        }

        $phone = $user->getNumTel();
        if (!$phone || !is_string($phone) || !preg_match('/^\d{8}$/', $phone)) {
            $logger?->warning('Invalid or missing phone number for user ' . $user->getEmail() . ' (num_tel: ' . ($phone ?? 'null') . ')');
            return new Response('Invalid or missing phone number. Please update your profile.', 400);
        }

        $normalizedPhone = '+216' . ltrim($phone, '0');
        $smsSent = $smsService->sendSms(
            $normalizedPhone,
            'Your post has been published successfully!'
        );

        if ($smsSent) {
            $logger?->info('SMS sent to ' . $normalizedPhone . ' for user ' . $user->getEmail());
            return new Response('SMS sent successfully!');
        } else {
            $logger?->error('Failed to send SMS to ' . $normalizedPhone);
            return new Response('Failed to send SMS.', 500);
        }
    }
}