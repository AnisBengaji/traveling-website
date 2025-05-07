<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

class BadWordFilterService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $apiKey;
    private string $apiUrl;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger, string $apiKey, string $apiUrl)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
    }

    public function filterText(string $text): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'content' => $text,
                    'censor-character' => '*',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray();

            $this->logger->debug('Bad Words API response', [
                'status_code' => $statusCode,
                'response' => $data,
            ]);

            if ($statusCode !== 200 || !isset($data['bad_words_total'])) {
                $this->logger->error('Invalid response from Bad Words API', [
                    'status_code' => $statusCode,
                    'response' => $data,
                ]);
                return ['is-bad' => false, 'bad-words' => [], 'error' => 'Invalid API response'];
            }

            $badWords = isset($data['bad_words_list']) ? array_column($data['bad_words_list'], 'word') : [];
            $isBad = $data['bad_words_total'] > 0;

            return [
                'is-bad' => $isBad,
                'bad-words' => $badWords,
                'censored-content' => $data['censored_content'] ?? $text,
            ];
        } catch (HttpExceptionInterface $e) {
            $this->logger->error('HTTP error in Bad Words API request', [
                'message' => $e->getMessage(),
                'status_code' => $e->getResponse()->getStatusCode(),
            ]);
            return ['is-bad' => false, 'bad-words' => [], 'error' => 'HTTP error: ' . $e->getMessage()];
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Transport error in Bad Words API request', [
                'message' => $e->getMessage(),
            ]);
            return ['is-bad' => false, 'bad-words' => [], 'error' => 'Network error: ' . $e->getMessage()];
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in Bad Words API request', [
                'message' => $e->getMessage(),
            ]);
            return ['is-bad' => false, 'bad-words' => [], 'error' => 'Unexpected error: ' . $e->getMessage()];
        }
    }
}