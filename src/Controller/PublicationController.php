<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Form\PublicationType;
use App\Form\FrontendPublicationType;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Psr\Log\LoggerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Security;
use TCPDF;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/publication')]
final class PublicationController extends AbstractController
{
    private $logger;
    private $security;
    private $validator;

    public function __construct(LoggerInterface $logger, Security $security, ValidatorInterface $validator)
    {
        $this->logger = $logger;
        $this->security = $security;
        $this->validator = $validator;
    }
    #[Route('/chart', name: 'publication_chart', methods: ['GET'], priority: 10)]
    #[IsGranted('ROLE_ADMIN')]
    public function chart(PublicationRepository $publicationRepository): Response
    {
        try {
            $this->logger->debug('Accessing chart route');

            

            $dataCategory = $publicationRepository->countByCategory();
            $dataComments = $publicationRepository->countByComments();

            $this->logger->debug('Repository data', [
                'dataCategory' => $dataCategory,
                'dataComments' => $dataComments,
            ]);

           
            $labelsCategory = !empty($dataCategory) ? array_map(fn($entry) => $entry['category'] ?? 'Unknown', $dataCategory) : [];
            $valuesCategory = !empty($dataCategory) ? array_map(fn($entry) => $entry['count'] ?? 0, $dataCategory) : [];

           
            $labelsComments = !empty($dataComments) ? array_map(fn($entry) => $entry['publication'] ?? 'Unknown', $dataComments) : [];
            $valuesComments = !empty($dataComments) ? array_map(fn($entry) => $entry['count'] ?? 0, $dataComments) : [];

           
            $publications = $publicationRepository->findBy([], ['datePublication' => 'DESC']);
            $labelsLikesDislikes = [];
            $dataLikes = [];
            $dataDislikes = [];

            foreach ($publications as $publication) {
                $labelsLikesDislikes[] = $publication->getTitle();
                $dataLikes[] = $publication->getLikes()->count();
                $dataDislikes[] = $publication->getDislikes()->count();
            }

            $this->logger->debug('Likes and Dislikes data', [
                'labelsLikesDislikes' => $labelsLikesDislikes,
                'dataLikes' => $dataLikes,
                'dataDislikes' => $dataDislikes,
            ]);

            return $this->render('publication/chart.html.twig', [
                'labelsCategory' => $labelsCategory,
                'dataCategory' => $valuesCategory,
                'labelsComments' => $labelsComments,
                'dataComments' => $valuesComments,
                'labelsLikesDislikes' => $labelsLikesDislikes,
                'dataLikes' => $dataLikes,
                'dataDislikes' => $dataDislikes,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error in chart: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->render('publication/error.html.twig', [
                'error' => 'Unable to load chart data: ' . $e->getMessage(),
            ], new Response('', 500));
        }
    }

    #[Route('/', name: 'app_publication_index', methods: ['GET'])]
    public function index(PublicationRepository $publicationRepository, Request $request, PaginatorInterface $paginator): Response
    {
        try {
            $query = $publicationRepository->createQueryBuilder('p')
                ->orderBy('p.datePublication', 'DESC')
                ->getQuery();

            $pagination = $paginator->paginate(
                $query,
                $request->query->getInt('page', 1),
                10
            );

            return $this->render('publication/index.html.twig', [
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error in index: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            throw $this->createNotFoundException('Unable to load publications. Error: ' . $e->getMessage());
        }
    }

    #[Route('/new', name: 'app_publication_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        try {
            $publication = new Publication();
            $user = $this->security->getUser();
            if (!$user) {
                $this->logger->warning('Unauthorized attempt to create publication');
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => false, 'message' => 'You must be logged in to create a publication.'], 403);
                }
                $this->addFlash('error', 'You must be logged in to create a publication.');
                return $this->redirectToRoute('app_login');
            }
            $publication->setAuthor($user);

            $form = $this->createForm(PublicationType::class, $publication);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $imageSource = $form->get('imageSource')->getData();

                if ($imageSource === 'file') {
                    $imageFile = $form->get('imageFile')->getData();
                    if ($imageFile) {
                        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                        try {
                            $imageFile->move($this->getParameter('publication_images_directory'), $newFilename);
                            $publication->setImage($newFilename);
                        } catch (FileException $e) {
                            $this->logger->error('Image upload failed: ' . $e->getMessage(), ['exception' => $e]);
                            if ($request->isXmlHttpRequest()) {
                                return new JsonResponse(['success' => false, 'message' => 'Image upload error: ' . $e->getMessage()], 400);
                            }
                            $this->addFlash('error', 'Image upload error: ' . $e->getMessage());
                            return $this->render('publication/new.html.twig', [
                                'publication' => $publication,
                                'form' => $form->createView(),
                            ]);
                        }
                    }
                } elseif ($imageSource === 'url') {
                    $imageUrl = $form->get('imageUrl')->getData();
                    if ($imageUrl) {
                        try {
                            $client = HttpClient::create();
                            $response = $client->request('GET', $imageUrl);
                            $contentType = $response->getHeaders()['content-type'][0] ?? '';
                            $extension = $this->getExtensionFromContentType($contentType);

                            if (!$extension) {
                                $this->logger->error('Invalid image format for URL: ' . $imageUrl);
                                if ($request->isXmlHttpRequest()) {
                                    return new JsonResponse(['success' => false, 'message' => 'Invalid image format (PNG or JPEG expected).'], 400);
                                }
                                $this->addFlash('error', 'Invalid image format (PNG or JPEG expected).');
                                return $this->render('publication/new.html.twig', [
                                    'publication' => $publication,
                                    'form' => $form->createView(),
                                ]);
                            }

                            $safeFilename = $slugger->slug(pathinfo($imageUrl, PATHINFO_FILENAME));
                            $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;
                            $filePath = $this->getParameter('publication_images_directory') . '/' . $newFilename;
                            file_put_contents($filePath, $response->getContent());

                            $publication->setImage($newFilename);
                        } catch (\Exception $e) {
                            $this->logger->error('Image download failed: ' . $e->getMessage(), ['exception' => $e]);
                            if ($request->isXmlHttpRequest()) {
                                return new JsonResponse(['success' => false, 'message' => 'Image download error: ' . $e->getMessage()], 400);
                            }
                            $this->addFlash('error', 'Image download error: ' . $e->getMessage());
                            return $this->render('publication/new.html.twig', [
                                'publication' => $publication,
                                'form' => $form->createView(),
                            ]);
                        }
                    }
                }

                $publication->setDatePublication(new \DateTime());
                $entityManager->persist($publication);
                $entityManager->flush();

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'data' => [
                            'idPublication' => $publication->getIdPublication(),
                            'title' => $publication->getTitle(),
                            'author' => [
                                'id' => $publication->getAuthor()->getId(),
                                'name' => trim($publication->getAuthor()->getNom() . ' ' . $publication->getAuthor()->getPrenom()),
                                'email' => $publication->getAuthor()->getEmail(),
                            ],
                            'contenu' => $publication->getContenu(),
                            'image' => $publication->getImage() ? '/Uploads/publications/' . $publication->getImage() : null,
                            'categoryId' => $publication->getCategory() ? $publication->getCategory()->getIdCategory() : null,
                            'datePublication' => $publication->getDatePublication()->format('M j, Y \a\t g:ia'),
                            'editUrl' => $this->generateUrl('app_publication_edit', ['id' => $publication->getIdPublication()]),
                            'showUrl' => $this->generateUrl('app_publication_show', ['id' => $publication->getIdPublication()]),
                            'commentUrl' => $this->generateUrl('app_comment_new', ['id_publication' => $publication->getIdPublication()]),
                        ]
                    ]);
                }

                $this->addFlash('success', 'Publication created successfully!');
                return $this->redirectToRoute('app_publication_index');
            }

            if ($form->isSubmitted() && !$form->isValid()) {
                $errors = $form->getErrors(true);
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                $this->logger->error('Form validation errors in new: ' . implode(', ', $errorMessages), ['form_data' => $request->request->all()]);
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => false, 'message' => 'Form validation failed: ' . implode(', ', $errorMessages)], 400);
                }
                $this->addFlash('error', 'Please correct the errors in the form: ' . implode(', ', $errorMessages));
            }

            return $this->render('publication/new.html.twig', [
                'publication' => $publication,
                'form' => $form->createView(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error in new: ' . $e->getMessage(), ['exception' => $e]);
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
            }
            $this->addFlash('error', 'An error occurred while creating the publication.');
            return $this->render('publication/new.html.twig', [
                'publication' => $publication,
                'form' => $form->createView(),
            ]);
        }
    }

    #[Route('/frontend/new', name: 'app_frontend_publication_new', methods: ['GET', 'POST'])]
    public function newFrontend(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        try {
            $publication = new Publication();
            $user = $this->security->getUser();
            if (!$user) {
                $this->logger->warning('Unauthorized attempt to create publication', ['ip' => $request->getClientIp()]);
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => false, 'message' => 'You must be logged in to create a publication.'], 403);
                }
                $this->addFlash('error', 'You must be logged in to create a publication.');
                return $this->redirectToRoute('app_login');
            }
            $publication->setAuthor($user);

            $form = $this->createForm(FrontendPublicationType::class, $publication);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                
                $formData = $request->request->all();
                $files = $request->files->all();
                $this->logger->debug('Form submission data: ' . json_encode($formData) . ', Files: ' . json_encode(array_keys($files)));

                if ($form->isValid()) {
                    
                
                    $imageFile = $form->get('image')->getData();
                    if ($imageFile) {
                        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                        try {
                            $uploadDir = $this->getParameter('publication_images_directory');
                            if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                                throw new FileException('Upload directory is not writable: ' . $uploadDir);
                            }
                            $imageFile->move($uploadDir, $newFilename);
                            $publication->setImage($newFilename);
                        } catch (FileException $e) {
                            $this->logger->error('Image upload failed: ' . $e->getMessage(), ['exception' => $e]);
                            if ($request->isXmlHttpRequest()) {
                                return new JsonResponse(['success' => false, 'message' => 'Image upload error: ' . $e->getMessage()], 400);
                            }
                            $this->addFlash('error', 'Image upload error: ' . $e->getMessage());
                            return $this->render('community.html.twig', [
                                'publication' => $publication,
                                'publication_form' => $form->createView(),
                            ]);
                        }
                    }

                    
                    if (!$publication->getCategory()) {
                        $this->logger->error('No category selected for publication');
                        if ($request->isXmlHttpRequest()) {
                            return new JsonResponse(['success' => false, 'message' => 'Please select a category.'], 400);
                        }
                        $this->addFlash('error', 'Please select a category.');
                        return $this->render('community.html.twig', [
                            'publication' => $publication,
                            'publication_form' => $form->createView(),
                        ]);
                    }

                    
                    $errors = $this->validator->validate($publication);
                    if (count($errors) > 0) {
                        $errorMessages = [];
                        foreach ($errors as $error) {
                            $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                        }
                        $this->logger->error('Entity validation errors: ' . implode(', ', $errorMessages));
                        if ($request->isXmlHttpRequest()) {
                            return new JsonResponse(['success' => false, 'message' => 'Validation failed: ' . implode(', ', $errorMessages)], 400);
                        }
                        $this->addFlash('error', 'Validation failed: ' . implode(', ', $errorMessages));
                        return $this->render('community.html.twig', [
                            'publication' => $publication,
                            'publication_form' => $form->createView(),
                        ]);
                    }

                    
                    try {
                        $publication->setDatePublication(new \DateTime());
                        $entityManager->persist($publication);
                        $entityManager->flush();
                    } catch (\Exception $e) {
                        $this->logger->error('Database error: ' . $e->getMessage(), ['exception' => $e]);
                        if ($request->isXmlHttpRequest()) {
                            return new JsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
                        }
                        $this->addFlash('error', 'Database error: ' . $e->getMessage());
                        return $this->render('community.html.twig', [
                            'publication' => $publication,
                            'publication_form' => $form->createView(),
                        ]);
                    }

                    $this->logger->info('Publication created successfully: ID ' . $publication->getIdPublication(), [
                        'user_id' => $user->getId(),
                        'title' => $publication->getTitle(),
                    ]);

                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse([
                            'success' => true,
                            'data' => [
                                'idPublication' => $publication->getIdPublication(),
                                'title' => $publication->getTitle(),
                                'author' => [
                                    'id' => $publication->getAuthor()->getId(),
                                    'name' => trim($publication->getAuthor()->getNom() . ' ' . $publication->getAuthor()->getPrenom()),
                                    'email' => $publication->getAuthor()->getEmail(),
                                ],
                                'contenu' => $publication->getContenu(),
                                'image' => $publication->getImage() ? '/Uploads/publications/' . $publication->getImage() : null,
                                'categoryId' => $publication->getCategory() ? $publication->getCategory()->getIdCategory() : null,
                                'datePublication' => $publication->getDatePublication()->format('M j, Y \a\t g:ia'),
                                'editUrl' => $this->generateUrl('app_publication_edit', ['id' => $publication->getIdPublication()]),
                                'showUrl' => $this->generateUrl('app_publication_show', ['id' => $publication->getIdPublication()]),
                                'commentUrl' => $this->generateUrl('app_comment_new', ['id_publication' => $publication->getIdPublication()]),
                            ]
                        ]);
                    }

                    $this->addFlash('success', 'Publication created successfully!');
                    return $this->redirectToRoute('app_community');
                } else {
                    $errors = $form->getErrors(true);
                    $errorMessages = [];
                    foreach ($errors as $error) {
                        $errorMessages[] = $error->getMessage();
                    }
                    $this->logger->error('Form validation errors in newFrontend: ' . implode(', ', $errorMessages), ['form_data' => $formData]);
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse(['success' => false, 'message' => 'Form validation failed: ' . implode(', ', $errorMessages)], 400);
                    }
                    $this->addFlash('error', 'Please correct the errors in the form: ' . implode(', ', $errorMessages));
                }
            }

            return $this->render('community.html.twig', [
                'publication' => $publication,
                'publication_form' => $form->createView(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error in newFrontend: ' . $e->getMessage(), ['exception' => $e]);
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
            }
            $this->addFlash('error', 'An error occurred while creating the publication.');
            return $this->render('community.html.twig', [
                'publication' => $publication,
                'publication_form' => $form->createView(),
            ]);
        }
    }

    #[Route('/{id}', name: 'app_publication_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Publication $publication): Response
    {
        try {
            return $this->render('publication/show.html.twig', [
                'publication' => $publication,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error in show: ' . $e->getMessage(), ['exception' => $e]);
            throw $this->createNotFoundException('Publication not found.');
        }
    }

    #[Route('/{id}/edit', name: 'app_publication_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Publication $publication, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        try {
            $form = $this->createForm(PublicationType::class, $publication);
            $originalImage = $publication->getImage();
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $imageSource = $form->get('imageSource')->getData();

                    if ($imageSource === 'file') {
                        $imageFile = $form->get('imageFile')->getData();
                        if ($imageFile) {
                            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                            $safeFilename = $slugger->slug($originalFilename);
                            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                            try {
                                $imageFile->move($this->getParameter('publication_images_directory'), $newFilename);
                                if ($originalImage) {
                                    @unlink($this->getParameter('publication_images_directory') . '/' . $originalImage);
                                }
                                $publication->setImage($newFilename);
                            } catch (FileException $e) {
                                $this->logger->error('Image upload failed: ' . $e->getMessage(), ['exception' => $e]);
                                if ($request->isXmlHttpRequest()) {
                                    return new JsonResponse(['success' => false, 'message' => 'Image upload error: ' . $e->getMessage()], 400);
                                }
                                $this->addFlash('error', 'Image upload error: ' . $e->getMessage());
                                return $this->render('publication/edit.html.twig', [
                                    'publication' => $publication,
                                    'form' => $form->createView(),
                                ]);
                            }
                        }
                    } elseif ($imageSource === 'url') {
                        $imageUrl = $form->get('imageUrl')->getData();
                        if ($imageUrl) {
                            try {
                                $client = HttpClient::create();
                                $response = $client->request('GET', $imageUrl);
                                $contentType = $response->getHeaders()['content-type'][0] ?? '';
                                $extension = $this->getExtensionFromContentType($contentType);

                                if (!$extension) {
                                    $this->logger->error('Invalid image format for URL: ' . $imageUrl);
                                    if ($request->isXmlHttpRequest()) {
                                        return new JsonResponse(['success' => false, 'message' => 'Invalid image format (PNG or JPEG expected).'], 400);
                                    }
                                    $this->addFlash('error', 'Invalid image format (PNG or JPEG expected).');
                                    return $this->render('publication/edit.html.twig', [
                                        'publication' => $publication,
                                        'form' => $form->createView(),
                                    ]);
                                }

                                $safeFilename = $slugger->slug(pathinfo($imageUrl, PATHINFO_FILENAME));
                                $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;
                                $filePath = $this->getParameter('publication_images_directory') . '/' . $newFilename;
                                file_put_contents($filePath, $response->getContent());

                                if ($originalImage) {
                                    @unlink($this->getParameter('publication_images_directory') . '/' . $originalImage);
                                }
                                $publication->setImage($newFilename);
                            } catch (\Exception $e) {
                                $this->logger->error('Image download failed: ' . $e->getMessage(), ['exception' => $e]);
                                if ($request->isXmlHttpRequest()) {
                                    return new JsonResponse(['success' => false, 'message' => 'Image download error: ' . $e->getMessage()], 400);
                                }
                                $this->addFlash('error', 'Image download error: ' . $e->getMessage());
                                return $this->render('publication/edit.html.twig', [
                                    'publication' => $publication,
                                    'form' => $form->createView(),
                                ]);
                            }
                        }
                    } elseif ($imageSource === null && $originalImage) {
                        @unlink($this->getParameter('publication_images_directory') . '/' . $originalImage);
                        $publication->setImage(null);
                    }

                    $errors = $this->validator->validate($publication);
                    if (count($errors) > 0) {
                        $errorMessages = [];
                        foreach ($errors as $error) {
                            $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                        }
                        $this->logger->error('Validation errors in edit: ' . implode(', ', $errorMessages));
                        if ($request->isXmlHttpRequest()) {
                            return new JsonResponse(['success' => false, 'message' => 'Validation failed: ' . implode(', ', $errorMessages)], 400);
                        }
                        $this->addFlash('error', 'Validation failed: ' . implode(', ', $errorMessages));
                        return $this->render('publication/edit.html.twig', [
                            'publication' => $publication,
                            'form' => $form->createView(),
                        ]);
                    }

                    try {
                        $entityManager->flush();
                    } catch (\Exception $e) {
                        $this->logger->error('Database error in edit: ' . $e->getMessage(), ['exception' => $e]);
                        if ($request->isXmlHttpRequest()) {
                            return new JsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
                        }
                        $this->addFlash('error', 'Database error: ' . $e->getMessage());
                        return $this->render('publication/edit.html.twig', [
                            'publication' => $publication,
                            'form' => $form->createView(),
                        ]);
                    }

                    $this->addFlash('success', 'Publication updated successfully.');
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse([
                            'success' => true,
                            'data' => [
                                'idPublication' => $publication->getIdPublication(),
                                'title' => $publication->getTitle(),
                                'author' => [
                                    'id' => $publication->getAuthor()->getId(),
                                    'name' => trim($publication->getAuthor()->getNom() . ' ' . $publication->getAuthor()->getPrenom()),
                                    'email' => $publication->getAuthor()->getEmail(),
                                ],
                                'contenu' => $publication->getContenu(),
                                'image' => $publication->getImage() ? '/Uploads/publications/' . $publication->getImage() : null,
                                'categoryId' => $publication->getCategory() ? $publication->getCategory()->getIdCategory() : null,
                                'datePublication' => $publication->getDatePublication()->format('M j, Y \a\t g:ia'),
                                'editUrl' => $this->generateUrl('app_publication_edit', ['id' => $publication->getIdPublication()]),
                                'showUrl' => $this->generateUrl('app_publication_show', ['id' => $publication->getIdPublication()]),
                                'commentUrl' => $this->generateUrl('app_comment_new', ['id_publication' => $publication->getIdPublication()]),
                            ]
                        ]);
                    }
                    return $this->redirectToRoute('app_publication_index');
                } else {
                    $errors = $form->getErrors(true);
                    $errorMessages = [];
                    foreach ($errors as $error) {
                        $errorMessages[] = $error->getMessage();
                    }
                    $this->logger->error('Form validation errors in edit: ' . implode(', ', $errorMessages), ['form_data' => $request->request->all()]);
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse(['success' => false, 'message' => 'Form validation failed: ' . implode(', ', $errorMessages)], 400);
                    }
                    $this->addFlash('error', 'Please correct the errors in the form: ' . implode(', ', $errorMessages));
                }
            }

            return $this->render('publication/edit.html.twig', [
                'publication' => $publication,
                'form' => $form->createView(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error in edit: ' . $e->getMessage(), ['exception' => $e]);
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
            }
            $this->addFlash('error', 'An error occurred while editing the publication.');
            return $this->render('publication/edit.html.twig', [
                'publication' => $publication,
                'form' => $form->createView(),
            ]);
        }
    }

    #[Route('/{id}/delete', name: 'app_publication_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Publication $publication, EntityManagerInterface $entityManager): Response
    {
        try {
            $user = $this->security->getUser();
            if (!$user || $publication->getAuthor()->getId() !== $user->getId()) {
                $this->logger->warning('Unauthorized attempt to delete publication', ['publication_id' => $publication->getIdPublication(), 'user_id' => $user ? $user->getId() : null]);
                $this->addFlash('error', 'You are not authorized to delete this publication.');
                return $this->redirectToRoute('app_publication_index');
            }

            if ($this->isCsrfTokenValid('delete' . $publication->getIdPublication(), $request->request->get('_token'))) {
                if ($publication->getImage()) {
                    @unlink($this->getParameter('publication_images_directory') . '/' . $publication->getImage());
                }

                $entityManager->remove($publication);
                $entityManager->flush();

                $this->addFlash('success', 'Publication deleted successfully.');
            }

            return $this->redirectToRoute('app_publication_index');
        } catch (\Exception $e) {
            $this->logger->error('Error in delete: ' . $e->getMessage(), ['exception' => $e]);
            $this->addFlash('error', 'Failed to delete publication: ' . $e->getMessage());
            return $this->redirectToRoute('app_publication_index');
        }
    }

    #[Route('/stats', name: 'app_publication_stats', methods: ['GET'])]
    public function stats(PublicationRepository $publicationRepository): Response
    {
        try {
            $data = $publicationRepository->createQueryBuilder('p')
                ->select('c.nomCategory AS category_name, COUNT(p.id_publication) AS publication_count')
                ->join('p.category', 'c')
                ->groupBy('c.idCategory')
                ->orderBy('publication_count', 'DESC')
                ->getQuery()
                ->getResult();
            $labels = array_column($data, 'category_name');
            $counts = array_column($data, 'publication_count');

            return $this->render('publication/stats.html.twig', [
                'labels' => json_encode($labels),
                'counts' => json_encode($counts),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error in stats: ' . $e->getMessage(), ['exception' => $e]);
            $this->addFlash('error', 'Unable to load publication statistics.');
            return $this->redirectToRoute('app_publication_index');
        }
    }

    private function getExtensionFromContentType(string $contentType): ?string
    {
        return match (strtolower($contentType)) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            default => null,
        };
    }


    #[Route('/search-posts', name: 'search_posts', methods: ['GET'])]
    public function searchPosts(Request $request, PublicationRepository $publicationRepository, LoggerInterface $logger): JsonResponse
    {
        try {
            $query = $request->query->get('query', '');
            $logger->info('Search query received: ' . $query);
            $publications = $publicationRepository->searchByTitleOrContent($query);

            $data = array_map(function (Publication $publication) {
                $author = $publication->getAuthor();
                return [
                    'idPublication' => $publication->getIdPublication(),
                    'title' => $publication->getTitle(),
                    'contenu' => $publication->getContenu(),
                    'image' => $publication->getImage() ? '/Uploads/publications/' . $publication->getImage() : null,
                    'datePublication' => $publication->getDatePublication()->format('M j, Y'),
                    'categoryId' => $publication->getCategory() ? $publication->getCategory()->getIdCategory() : null,
                    'author' => [
                        'nom' => $author ? $author->getNom() : null,
                        'prenom' => $author ? $author->getPrenom() : null,
                        'email' => $author ? $author->getEmail() : 'Anonymous',
                        'id' => $author ? $author->getId() : null,
                    ],
                    'likes' => count($publication->getLikes()),
                    'dislikes' => count($publication->getDislikes()),
                    'commentsCount' => count($publication->getComments()),
                    'isAuthor' => $this->getUser() && $author && $author->getId() === $this->getUser()->getId(),
                    'isLiked' => $this->getUser() ? $publication->isLikedByUser($this->getUser()) : false,
                    'isDisliked' => $this->getUser() ? $publication->isDislikedByUser($this->getUser()) : false,
                    'editUrl' => $this->getUser() && $author && $author->getId() === $this->getUser()->getId()
                        ? $this->generateUrl('edit_publication', ['id_publication' => $publication->getIdPublication()])
                        : null,
                    'showUrl' => $this->generateUrl('app_publication_show', ['id' => $publication->getIdPublication()]),
                ];
            }, $publications);

            return $this->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            $logger->error('Search error: ' . $e->getMessage(), ['exception' => $e]);
            return $this->json([
                'success' => false,
                'message' => 'An error occurred while searching: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/publication/{id}/download-pdf', name: 'app_publication_download_pdf', methods: ['GET'])]
    public function downloadPdf(
        Publication $publication,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): Response {
        try {
          
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

           
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor($publication->getAuthor()->getNom() ?? 'Unknown');
            $pdf->SetTitle($publication->getTitle());
            $pdf->SetSubject('Publication PDF');
            $pdf->SetKeywords('Publication, PDF, ' . $publication->getCategory()->getNomCategory());

           
            $pdf->SetHeaderData('', 0, 'My Application', $publication->getTitle(), [0, 64, 255], [0, 64, 128]);
            $pdf->setFooterData([0, 64, 0], [0, 64, 128]);

            
            $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
            $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);

            
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

           
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            
            $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

           
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            // Set font
            $pdf->SetFont('helvetica', '', 12);

            // Add a page
            $pdf->AddPage();

            // Create HTML content for the PDF
            $html = '
                <style>
                    h1 { color: #003087; font-size: 24pt; text-align: center; border-bottom: 2px solid #003087; padding-bottom: 10px; }
                    h2 { color: #005566; font-size: 18pt; }
                    p { font-size: 12pt; line-height: 1.6; }
                    .meta { color: #555; font-size: 10pt; font-style: italic; }
                    .content { text-align: justify; }
                    .image { text-align: center; margin: 20px 0; }
                </style>
                <h1>' . htmlspecialchars($publication->getTitle()) . '</h1>
                <p class="meta">Author: ' . htmlspecialchars($publication->getAuthor()->getNom() ?? 'Unknown') . '</p>
                <p class="meta">Published: ' . $publication->getDatePublication()->format('M j, Y') . '</p>
                <p class="meta">Category: ' . htmlspecialchars($publication->getCategory()->getNomCategory()) . '</p>
                <p class="meta">Visibility: ' . ucfirst($publication->getVisibility()) . '</p>';

         
            if ($publication->getImage()) {
                $imagePath = $this->getParameter('publication_images_directory') . '/' . $publication->getImage();
                if (file_exists($imagePath)) {
                    $html .= '<div class="image"><img src="' . $imagePath . '" width="300" /></div>';
                } else {
                    $logger->warning('Image file not found for publication', [
                        'id_publication' => $publication->getIdPublication(),
                        'image_path' => $imagePath,
                    ]);
                }
            }

            $html .= '
                <h2>Content</h2>
                <p class="content">' . nl2br(htmlspecialchars($publication->getContenu())) . '</p>';

          
            $pdf->writeHTML($html, true, false, true, false, '');

            
            $filename = 'publication_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $publication->getTitle()) . '.pdf';
            $pdfContent = $pdf->Output($filename, 'S'); 

            $response = new Response($pdfContent);
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            );
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', $disposition);

            $logger->info('PDF generated and downloaded for publication', [
                'id_publication' => $publication->getIdPublication(),
                'filename' => $filename,
            ]);

            return $response;
        } catch (\Exception $e) {
            $logger->error('Failed to generate PDF for publication', [
                'id_publication' => $publication->getIdPublication(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('error', 'Failed to generate PDF: ' . $e->getMessage());
            return $this->redirectToRoute('app_publication_show', ['id' => $publication->getIdPublication()]);
        }
    }#[Route('/publication/download-all-pdf', name: 'app_publication_download_all_pdf', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function downloadAllPdf(
        PublicationRepository $publicationRepository,
        LoggerInterface $logger
    ): Response {
        try {
           
            $publications = $publicationRepository->findBy([], ['datePublication' => 'DESC']);

            if (empty($publications)) {
                $logger->warning('No publications found for PDF download');
                $this->addFlash('warning', 'No publications available to download.');
                return $this->redirectToRoute('app_publication_index');
            }

            
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Carint Admin');
            $pdf->SetTitle('All Publications');
            $pdf->SetSubject('All Publications PDF');
            $pdf->SetKeywords('Publications, PDF, Carint');

            
            $pdf->SetHeaderData('', 0, 'Carint Admin', 'All Publications', [0, 64, 255], [0, 64, 128]);
            $pdf->setFooterData([0, 64, 0], [0, 64, 128]);

            
            $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
            $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);

            
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

           
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

           
            $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

            
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            
            $pdf->SetFont('helvetica', '', 12);

           
            $pdf->AddPage();

           
            $html = '
                <style>
                    h1 { color: #003087; font-size: 24pt; text-align: center; border-bottom: 2px solid #003087; padding-bottom: 10px; }
                    h2 { color: #005566; font-size: 18pt; }
                    p { font-size: 12pt; line-height: 1.6; }
                    .meta { color: #555; font-size: 10pt; font-style: italic; }
                    .content { text-align: justify; }
                    .image { text-align: center; margin: 20px 0; }
                    hr { border: 1px solid #003087; margin: 20px 0; }
                </style>
                <h1>All Publications</h1>';

            foreach ($publications as $index => $publication) {
                $html .= '
                    <h2>' . htmlspecialchars($publication->getTitle()) . '</h2>
                    <p class="meta">Author: ' . htmlspecialchars($publication->getAuthor()->getNom() ?? 'Unknown') . '</p>
                    <p class="meta">Published: ' . $publication->getDatePublication()->format('M j, Y') . '</p>
                    <p class="meta">Category: ' . htmlspecialchars($publication->getCategory()->getNomCategory()) . '</p>
                    <p class="meta">Visibility: ' . ucfirst($publication->getVisibility()) . '</p>';

                
                if ($publication->getImage()) {
                    $imagePath = $this->getParameter('publication_images_directory') . '/' . $publication->getImage();
                    if (file_exists($imagePath)) {
                        $html .= '<div class="image"><img src="' . $imagePath . '" width="300" /></div>';
                    } else {
                        $logger->warning('Image file not found for publication', [
                            'id_publication' => $publication->getIdPublication(),
                            'image_path' => $imagePath,
                        ]);
                    }
                }

                $html .= '
                    <h3>Content</h3>
                    <p class="content">' . nl2br(htmlspecialchars($publication->getContenu())) . '</p>';

                
                if ($index < count($publications) - 1) {
                    $html .= '<hr>';
                    $pdf->writeHTML($html, true, false, true, false, '');
                    $pdf->AddPage();
                    $html = ''; 
                }
            }

         
            $pdf->writeHTML($html, true, false, true, false, '');

            // Output the PDF as a download
            $filename = 'all_publications.pdf';
            $pdfContent = $pdf->Output($filename, 'S'); // 'S' returns the PDF as a string

            $response = new Response($pdfContent);
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            );
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', $disposition);

            $logger->info('PDF generated and downloaded for all publications', [
                'publication_count' => count($publications),
                'filename' => $filename,
            ]);

            return $response;
        } catch (\Exception $e) {
            $logger->error('Failed to generate PDF for all publications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('error', 'Failed to generate PDF: ' . $e->getMessage());
            return $this->redirectToRoute('app_publication_index');
        }
    }
}
