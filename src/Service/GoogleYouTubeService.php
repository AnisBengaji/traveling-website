<?php

namespace App\Service;

use Google_Client;
use Google_Service_YouTube;

class GoogleYouTubeService
{
    private $youtube;

    public function __construct(string $apiKey)
    {
        $client = new Google_Client();
        $client->setDeveloperKey($apiKey);
        $this->youtube = new Google_Service_YouTube($client);
    }

    public function searchVideos(string $query, int $maxResults = 3): array
    {
        try {
            $searchResponse = $this->youtube->search->listSearch('snippet', [
                'q' => $query,
                'maxResults' => $maxResults,
                'type' => 'video',
                'videoEmbeddable' => 'true'
            ]);

            $videos = [];
            foreach ($searchResponse->getItems() as $searchResult) {
                $videos[] = [
                    'title' => $searchResult->getSnippet()->getTitle(),
                    'description' => $searchResult->getSnippet()->getDescription(),
                    'thumbnail' => $searchResult->getSnippet()->getThumbnails()->getMedium()->getUrl(),
                    'videoId' => $searchResult->getId()->getVideoId(),
                    'url' => 'https://www.youtube.com/watch?v=' . $searchResult->getId()->getVideoId(),
                    'embedUrl' => 'https://www.youtube.com/embed/' . $searchResult->getId()->getVideoId()
                ];
            }

            return $videos;
        } catch (\Exception $e) {
            // Log l'erreur si nécessaire
            return [];
        }
    }
} 