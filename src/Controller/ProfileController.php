<?php
namespace App\Controller;

use App\Form\ProfileEditType;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; // Corrected import for password hasher

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    private $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/profile1', name: 'profile1')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function showProfile1(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('admin/profile1.html.twig', [
            'user' => $user,
        ]);
    }


    #[Route('/profile', name: 'profile')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function showProfile(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('admin/profile2.html.twig', [
            'user' => $user,
        ]);
    }


    


    #[Route('/profile/edit', name: 'profile_edit')]
    public function edit(Request $request): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('profile');
        }

        return $this->render('profile/edit2.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/edit3', name: 'profile_edit3')]
    public function edit3(Request $request): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('profile1');
        }

        return $this->render('profile/edit3.html.twig', [
            'form' => $form->createView(),
        ]);
    }









    #[Route('/profile/change-password', name: 'profile_change_password')]
    public function changePassword(Request $request): Response
    {
        $user = $this->getUser(); // Get the currently authenticated user

        // Check if the user is logged in
        if (!$user instanceof UserInterface) {
            // If the user is not authenticated, redirect them to the login page
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get the form data
            $data = $form->getData();
            $currentPassword = $data['currentPassword'];
            $newPassword = $data['newPassword'];
            $confirmPassword = $data['confirmPassword'];

            // Check if the current password is correct
            if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->redirectToRoute('profile_change_password');
            }

            // Check if the new password matches the confirmation password
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'New password and confirmation do not match.');
                return $this->redirectToRoute('profile_change_password');
            }

            // Hash the new password
            $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));

            // Persist the updated user
            $this->entityManager->flush();

            $this->addFlash('success', 'Password changed successfully!');
            return $this->redirectToRoute('profile');
        }

        return $this->render('profile/change_password2.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/profile/change-password3', name: 'profile_change_password3')]
    public function changePassword3(Request $request): Response
    {
        $user = $this->getUser(); // Get the currently authenticated user

        // Check if the user is logged in
        if (!$user instanceof UserInterface) {
            // If the user is not authenticated, redirect them to the login page
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get the form data
            $data = $form->getData();
            $currentPassword = $data['currentPassword'];
            $newPassword = $data['newPassword'];
            $confirmPassword = $data['confirmPassword'];

            // Check if the current password is correct
            if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->redirectToRoute('profile_change_password3');
            }

            // Check if the new password matches the confirmation password
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'New password and confirmation do not match.');
                return $this->redirectToRoute('profile_change_password3');
            }

            // Hash the new password
            $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));

            // Persist the updated user
            $this->entityManager->flush();

            $this->addFlash('success', 'Password changed successfully!');
            return $this->redirectToRoute('profile1');
        }

        return $this->render('profile/change_password3.html.twig', [
            'form' => $form->createView(),
        ]);
    }


}
