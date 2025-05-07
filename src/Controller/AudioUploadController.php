<?php

// src/Controller/AudioUploadController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AudioUploadController extends AbstractController
{
    /**
     * @Route("/upload_audio", name="upload_audio", methods={"POST"})
     */
    public function upload(Request $request): Response
    {
        $audioFile = $request->files->get('audio');
        $publicationId = $request->request->get('publicationId');

        if ($audioFile) {
            $uploadDir = $this->getParameter('audio_directory'); // Define this parameter in services.yaml
            $audioFileName = uniqid() . '.' . $audioFile->guessExtension();
            $audioFile->move($uploadDir, $audioFileName);

            // Optionally, save the file path and publication ID to the database

            return new Response('File uploaded successfully', 200);
        }

        return new Response('No file uploaded', 400);
    }
}