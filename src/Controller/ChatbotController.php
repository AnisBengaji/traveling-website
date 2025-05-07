<?php

namespace App\Controller;

use App\Service\ChatbotService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ChatbotController extends AbstractController
{
    #[Route('/api/chatbot', name: 'chatbot', methods: ['POST'])]
    public function handle(Request $request, ChatbotService $chatbotService): JsonResponse
    {
        $message = $request->request->get('message');
        $response = $chatbotService->getResponse($message);
        return $this->json(['reply' => $response]);
    }

    
}