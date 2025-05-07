<?php

namespace App\Service;

class ChatbotService
{
    private array $responses = [
        'salut' => 'Bonjour ! Bienvenue chez Trripin, votre agence pour des événements inoubliables ! 😊 Comment puis-je vous aider ?',
        'aide' => 'Je suis là pour vous guider ! Vous voulez en savoir plus sur nos événements, nos offres, ou comment réserver ? Dites-moi tout !',
        'contact' => 'Contactez-nous à contact@trripin.com pour toute question ou demande personnalisée. On vous répond vite !',
        'offres' => 'Découvrez nos offres exclusives pour des concerts, festivals, et escapades culturelles ! Consultez notre site ou demandez-moi des détails. 🎉',
        'réservation' => 'Voici une liste d’événements disponibles ! Cliquez sur l’icône verte pour réserver ou l’icône bleue pour plus de détails.',
        'prix' => 'Nos prix varient selon les événements, à partir de 900 DT pour les expériences locales. Consultez nos offres pour plus d’infos !',
        'événements' => 'Concerts, festivals, expos... On a tout pour vous ! Dites-moi quel type d’événement vous intéresse, je vous guide !',
    ];

    public function getResponse(string $keyword): string
    {
        $keyword = strtolower(trim($keyword));
        return $this->responses[$keyword] ?? 'Désolé, je n’ai pas compris. Essayez "offres", "réservation", "contact", ou "événements" pour en savoir plus !';
    }

    public function getKeywords(): array
    {
        return array_keys($this->responses);
    }
}