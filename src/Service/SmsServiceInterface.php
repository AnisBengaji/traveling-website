<?php

namespace App\Service;

interface SmsServiceInterface
{
    /**
     * Send a test SMS message
     * 
     * @param string $phoneNumber The recipient's phone number
     * @param string $message The message content
     * @throws \Exception If sending fails
     */
    public function sendTestSms(string $phoneNumber, string $message): void;

    /**
     * Send notification about a new offer
     * 
     * @param \App\Entity\Offre $offre The new offer
     * @throws \Exception If sending fails
     */
    public function sendNewOfferNotification(\App\Entity\Offre $offre): void;
} 