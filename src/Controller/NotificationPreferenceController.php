<?php

namespace App\Controller;

use App\Entity\NotificationPreference;
use App\Entity\User;
use App\Form\NotificationPreferenceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationPreferenceController extends AbstractController
{
    #[Route('/', name: 'app_notification_preference')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $preference = $user->getNotificationPreference() ?? new NotificationPreference();
        
        if (!$user->getNotificationPreference()) {
            $preference->setUser($user);
            $user->setNotificationPreference($preference);
            $entityManager->persist($preference);
        }

        $form = $this->createForm(NotificationPreferenceType::class, $preference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Vos préférences de notification ont été mises à jour.');
            return $this->redirectToRoute('app_notification_preference');
        }

        return $this->render('notification_preference/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
} 