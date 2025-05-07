<?php
// src/Controller/EmailTestController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmailTestController extends AbstractController
{
    #[Route('/test-email')]
    public function testEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('noreply@yourdomain.com')
            ->to('your-email@example.com')  // Replace with your email
            ->subject('Test Email')
            ->text('This is a test email from Symfony.');

        $mailer->send($email);

        return new Response('Test email sent.');
    }
}
