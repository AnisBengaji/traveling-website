<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

class SmsService
{
    private $twilioClient;
    private $fromNumber;
    private $logger;

    public function __construct(string $twilioSid, string $twilioToken, string $fromNumber, ?LoggerInterface $logger = null)
    {
        $this->twilioClient = new Client($twilioSid, $twilioToken);
        $this->fromNumber = $fromNumber;
        $this->logger = $logger;
    }

    public function sendSms(string $to, string $message): bool
    {
        $this->logger?->debug('Sending SMS to: ' . $to . ', Message: ' . $message);
        try {
            $this->twilioClient->messages->create(
                $to,
                [
                    'from' => $this->fromNumber,
                    'body' => $message,
                ]
            );
            $this->logger?->info('SMS sent to ' . $to);
            return true;
        } catch (TwilioException $e) {
            $this->logger?->error('Failed to send SMS to ' . $to . ': ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')');
            throw $e; // Re-throw to allow caller to handle
        }
    }
}