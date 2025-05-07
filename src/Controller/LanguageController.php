<?php
namespace App\Controller;

use App\Form\LanguageSelectorType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LanguageController extends AbstractController
{
    #[Route('/change-language', name: 'app_change_language', methods: ['POST'])]
    public function changeLanguage(Request $request): Response
    {
        $form = $this->createForm(LanguageSelectorType::class, null, [
            'current_locale' => $request->getLocale(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $locale = $form->get('locale')->getData();
            $request->getSession()->set('_locale', $locale);

            // Debug: Log the locale change
            $this->addFlash('debug', sprintf('Locale set to: %s', $locale));

            // Redirect to the service page or the referer
            $referer = $request->headers->get('referer');
            return $this->redirect($referer ?: $this->generateUrl('landing_service'));
        }

        // If the form is invalid or not submitted, redirect to the service page
        return $this->redirectToRoute('landing_service');
    }
}