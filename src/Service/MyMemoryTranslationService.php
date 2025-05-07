<?php
namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DomCrawler\Crawler; // Add this import

class MyMemoryTranslationService
{
    private $httpClient;
    private $logger;

    public function __construct(HttpClientInterface $httpClient, ?LoggerInterface $logger = null)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    public function translatePage(string $htmlContent, string $targetLang): string
    {
        try {
            // Parse HTML with DomCrawler to extract translatable text
            $crawler = new Crawler($htmlContent);
            $textNodes = [];

            // Collect text from elements (excluding scripts, styles, and form inputs)
            $crawler->filter('body *')->each(function (Crawler $node) use (&$textNodes) {
                if (!in_array($node->nodeName(), ['script', 'style', 'input', 'textarea', 'select'])) {
                    $text = trim($node->text());
                    if ($text && !empty($text)) {
                        $textNodes[] = [
                            'text' => $text,
                            'node' => $node,
                        ];
                    }
                }
            });

            // Translate each text node
            $translatedHtml = $htmlContent;
            foreach ($textNodes as $nodeData) {
                $originalText = $nodeData['text'];
                $response = $this->httpClient->request('GET', 'https://api.mymemory.translated.net/get', [
                    'query' => [
                        'q' => $originalText,
                        'langpair' => 'auto|' . $targetLang,
                    ],
                ]);

                $data = $response->toArray();
                if (isset($data['responseData']['translatedText'])) {
                    $translatedText = $data['responseData']['translatedText'];
                    $translatedHtml = str_replace($originalText, $translatedText, $translatedHtml);
                } else {
                    $this->logger?->warning('Translation failed for text: ' . $originalText);
                }
            }

            return $translatedHtml;
        } catch (\Exception $e) {
            $this->logger?->error('MyMemory translation error: ' . $e->getMessage());
            return $htmlContent; // Fallback to original content
        }
    }
}