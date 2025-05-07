<?php
namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Publication;
use App\Form\FrontendCommentType;
use App\Form\FrontendPublicationType;
use App\Repository\PublicationRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\MyMemoryTranslationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\SmsService;

class LandingController extends AbstractController
{
    #[Route('/index', name: 'landing_index')]
    public function index(): Response
    {
        return $this->render('landing/index.html.twig');
    }
    #[Route('/index2', name: 'landing_index2')]
    public function index2(): Response
    {
        return $this->render('landing/index2.html.twig');
    }

    #[Route('/about', name: 'landing_about')]
    public function about(): Response
    {
        return $this->render('landing/about.html.twig');
    }

    #[Route('/contact', name: 'landing_contact')]
    public function contact(): Response
    {
        return $this->render('landing/contact.html.twig');
    }

    #[Route('/service', name: 'landing_service')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function service(
        PublicationRepository $publicationRepository,
        CategoryRepository $categoryRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        SmsService $smsService,
        MyMemoryTranslationService $translationService,
        ?LoggerInterface $logger = null
    ): Response {
        $publications = $publicationRepository->findBy([], ['datePublication' => 'DESC']);
        $categories = $categoryRepository->findAll();

        $newPublication = new Publication();
        $newPublication->setDatePublication(new \DateTime());
        $newPublication->setVisibility('public');
        $newPublication->setAuthor($this->getUser());

        $publicationForm = $this->createForm(FrontendPublicationType::class, $newPublication);
        $publicationForm->handleRequest($request);

        if ($publicationForm->isSubmitted()) {
            if ($publicationForm->isValid()) {
                $imageFile = $publicationForm->get('image')->getData();
                if ($imageFile) {
                    try {
                        $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                        $imageFile->move($this->getParameter('publication_images_directory'), $newFilename);
                        $newPublication->setImage($newFilename);
                    } catch (\Exception $e) {
                        $logger?->error('Image upload failed: ' . $e->getMessage());
                        if ($request->isXmlHttpRequest()) {
                            return new JsonResponse(['success' => false, 'message' => 'Image upload error: ' . $e->getMessage()], 400);
                        }
                        $this->addFlash('error', 'Image upload error: ' . $e->getMessage());
                        return $this->render('landing/service.html.twig', [
                            'publications' => $publications,
                            'categories' => $categories,
                            'forms' => [],
                            'publication_form' => $publicationForm->createView(),
                        ]);
                    }
                }

                try {
                    $entityManager->persist($newPublication);
                    $entityManager->flush();
                } catch (\Exception $e) {
                    $logger?->error('Database persist/flush failed: ' . $e->getMessage());
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
                    }
                    $this->addFlash('error', 'Failed to save publication: ' . $e->getMessage());
                    return $this->render('landing/service.html.twig', [
                        'publications' => $publications,
                        'categories' => $categories,
                        'forms' => [],
                        'publication_form' => $publicationForm->createView(),
                    ]);
                }

                
                $user = $this->getUser();
                $phone = $user->getNumTel();
                $smsSent = false;
                $phoneMissing = false;

                $logger?->debug('User: ' . $user->getEmail() . ', Raw phone: ' . ($phone ?? 'null'));

                if ($phone && is_numeric($phone) && preg_match('/^\d{8}$/', $phone)) {
                    $normalizedPhone = '+216' . ltrim($phone, '0');
                    $logger?->debug('Normalized phone: ' . $normalizedPhone);
                    try {
                        $smsSent = $smsService->sendSms(
                            $normalizedPhone,
                            "Your post '{$newPublication->getTitle()}' has been published!"
                        );
                        if ($smsSent) {
                            $logger?->info('SMS successfully sent to ' . $normalizedPhone);
                        } else {
                            $this->addFlash('warning', 'Publication created, but failed to send SMS notification.');
                            $logger?->warning('SMS sending failed for ' . $normalizedPhone . ': No specific error returned');
                        }
                    } catch (\Exception $e) {
                        $this->addFlash('warning', 'Publication created, but failed to send SMS notification: ' . $e->getMessage());
                        $logger?->error('SMS sending failed for ' . $normalizedPhone . ': ' . $e->getMessage());
                    }
                } else {
                    $phoneMissing = true;
                    $this->addFlash('warning', 'Publication created, but no valid phone number found for SMS notification.');
                    $logger?->warning('Invalid or missing phone number for user ' . $user->getEmail() . ' (num_tel: ' . ($phone ?? 'null') . ')');
                }

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'smsSent' => $smsSent,
                        'phoneMissing' => $phoneMissing,
                        'data' => [
                            'idPublication' => $newPublication->getIdPublication(),
                            'title' => $newPublication->getTitle(),
                            'author' => [
                                'nom' => $newPublication->getAuthor()->getNom(),
                                'prenom' => $newPublication->getAuthor()->getPrenom(),
                                'email' => $newPublication->getAuthor()->getEmail(),
                            ],
                            'contenu' => $newPublication->getContenu(),
                            'image' => $newPublication->getImage() ? '/Uploads/publications/' . $newPublication->getImage() : null,
                            'categoryId' => $newPublication->getCategory() ? $newPublication->getCategory()->getIdCategory() : null,
                            'datePublication' => $newPublication->getDatePublication()->format('M j, Y \a\t g:ia'),
                            'editUrl' => $this->generateUrl('edit_publication', ['id_publication' => $newPublication->getIdPublication()]),
                            'deleteUrl' => $this->generateUrl('delete_publication', ['id_publication' => $newPublication->getIdPublication()]),
                            'showUrl' => $this->generateUrl('app_publication_show', ['id' => $newPublication->getIdPublication()]),
                            'isAuthor' => true,
                        ],
                    ]);
                }

                $this->addFlash('success', 'Publication added successfully!');
                return $this->redirectToRoute('landing_service');
            } else {
                $errors = [];
                foreach ($publicationForm->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $logger?->warning('Form validation failed: ' . implode(', ', $errors));
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Form validation failed: ' . implode(', ', $errors),
                    ], 400);
                }
                $this->addFlash('error', 'Form validation failed: ' . implode(', ', $errors));
            }
        }

        
        $commentForms = [];
        foreach ($publications as $publication) {
            $comment = new Comment();
            $comment->setPublication($publication);
            $comment->setDateComment(new \DateTime());
            $commentForms[$publication->getIdPublication()] = $this->createForm(FrontendCommentType::class, $comment, [
                'action' => $this->generateUrl('app_comment_new', ['id_publication' => $publication->getIdPublication()]),
                'method' => 'POST',
                'csrf_token_id' => 'comment' . $publication->getIdPublication(),
            ])->createView();
        }

       
        $templateParams = [
            'publications' => $publications,
            'categories' => $categories,
            'forms' => $commentForms,
            'publication_form' => $publicationForm->createView(),
        ];

        $htmlContent = $this->renderView('landing/service.html.twig', $templateParams);

      
        $targetLang = $request->getSession()->get('_locale', 'en');

        
        if ($targetLang !== 'en') {
            $htmlContent = $translationService->translatePage($htmlContent, $targetLang);
        }

        return new Response($htmlContent);
    }


    #[Route('/publication/edit/{id_publication<\d+>}', name: 'edit_publication')]
    public function edit(Request $request, EntityManagerInterface $entityManager, ?Publication $publication, ?LoggerInterface $logger = null): Response
    {
        if (!$publication) {
            $logger?->warning('Publication not found for ID: ' . $request->attributes->get('id_publication'));
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Publication not found'], 404);
            }
            $this->addFlash('error', 'Publication not found');
            return $this->redirectToRoute('landing_service');
        }

        
        if ($this->getUser() !== $publication->getAuthor()) {
            $logger?->warning('Unauthorized edit attempt for publication ID: ' . $publication->getIdPublication() . ' by user: ' . ($this->getUser() ? $this->getUser()->getEmail() : 'anonymous'));
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Unauthorized to edit this publication'], 403);
            }
            $this->addFlash('error', 'You are not authorized to edit this publication');
            return $this->redirectToRoute('landing_service');
        }

        $editForm = $this->createForm(FrontendPublicationType::class, $publication);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $imageFile = $editForm->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($this->getParameter('publication_images_directory'), $newFilename);
                    if ($publication->getImage()) {
                        $oldImagePath = $this->getParameter('publication_images_directory') . '/' . $publication->getImage();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    $publication->setImage($newFilename);
                } catch (\Exception $e) {
                    $logger?->error('Image upload error: ' . $e->getMessage());
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse(['success' => false, 'message' => 'Image upload error: ' . $e->getMessage()], 500);
                    }
                    $this->addFlash('error', 'Image upload error: ' . $e->getMessage());
                    return $this->render('publication/edit_frontend.html.twig', [
                        'publication' => $publication,
                        'form' => $editForm->createView(),
                    ]);
                }
            }

            try {
                $entityManager->flush();
                $logger?->info('Publication updated successfully: ID ' . $publication->getIdPublication());
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Publication updated successfully',
                        'redirect' => $this->generateUrl('landing_service'),
                    ]);
                }
                $this->addFlash('success', 'Publication updated successfully!');
                return $this->redirectToRoute('landing_service');
            } catch (\Exception $e) {
                $logger?->error('Database error: ' . $e->getMessage());
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
                }
                $this->addFlash('error', 'Database error: ' . $e->getMessage());
                return $this->render('publication/edit_frontend.html.twig', [
                    'publication' => $publication,
                    'form' => $editForm->createView(),
                ]);
            }
        }

        if ($request->isXmlHttpRequest() && $editForm->isSubmitted()) {
            $logger?->warning('Invalid form data submitted for publication ID: ' . $publication->getIdPublication());
            return new JsonResponse(['success' => false, 'message' => 'Invalid form data'], 400);
        }

        return $this->render('publication/edit_frontend.html.twig', [
            'publication' => $publication,
            'form' => $editForm->createView(),
        ]);
    }

    #[Route('/publication/delete/{id_publication<\d+>}', name: 'delete_publication', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(Request $request, ?Publication $publication, EntityManagerInterface $entityManager, ?LoggerInterface $logger = null): JsonResponse
    {
        if (!$publication) {
            $logger?->warning('Publication not found for ID: ' . $request->attributes->get('id_publication'));
            return new JsonResponse(['success' => false, 'message' => 'Publication not found'], 404);
        }

       

    
        if ($this->getUser() !== $publication->getAuthor()) {
            $logger?->warning('Unauthorized delete attempt for publication ID: ' . $publication->getIdPublication() . ' by user: ' . ($this->getUser() ? $this->getUser()->getEmail() : 'anonymous'));
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized to delete this publication'], 403);
        }

        
        if (!$this->isCsrfTokenValid('delete_publication_' . $publication->getIdPublication(), $request->headers->get('X-CSRF-Token'))) {
            $logger?->warning('Invalid CSRF token for delete publication ID: ' . $publication->getIdPublication());
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }

        try {
           
            if ($publication->getImage()) {
                $imagePath = $this->getParameter('publication_images_directory') . '/' . $publication->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($publication);
            $entityManager->flush();
            $logger?->info('Publication deleted successfully: ID ' . $publication->getIdPublication());

            return new JsonResponse(['success' => true, 'message' => 'Publication deleted successfully']);
        } catch (\Exception $e) {
            $logger?->error('Failed to delete publication ID: ' . $publication->getIdPublication() . ' - ' . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => 'Failed to delete publication: ' . $e->getMessage()], 500);
        }
    }
    #[Route('/set-language/{locale}', name: 'set_language', requirements: ['locale' => 'en|fr|es'])]
    public function setLanguage(string $locale, SessionInterface $session, Request $request): Response
    {
        
        $session->set('_locale', $locale);

        
        $this->addFlash('debug', sprintf('Locale set to: %s', $locale));

     
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('landing_service'));
    }

    #[Route('/set-theme/{theme}', name: 'set_theme', requirements: ['theme' => 'light|dark'])]
    public function setTheme(string $theme, SessionInterface $session, Request $request): Response
    {
        $session->set('theme', $theme);
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('landing_service'));
    }



    
    
}