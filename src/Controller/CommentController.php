<?php
namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Publication;
use App\Form\FrontendCommentType;
use App\Form\CommentType;
use App\Service\BadWordFilterService;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use TCPDF;
use App\Repository\CommentRepository;

class CommentController extends AbstractController
{
    #[Route('/comment/new/{id_publication<\d+>}', name: 'app_comment_new', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function new(
        Request $request,
        ?Publication $publication,
        EntityManagerInterface $entityManager,
        SmsService $smsService,
        LoggerInterface $logger,
        BadWordFilterService $badWordFilterService
    ): Response {
        try {
            $logger->debug('Starting comment creation', [
                'id_publication' => $request->attributes->get('id_publication'),
                'user_id' => $this->getUser() ? $this->getUser()->getId() : null,
                'request_data' => $request->request->all(),
            ]);

            if (!$publication) {
                $logger->warning('Publication not found', ['id_publication' => $request->attributes->get('id_publication')]);
                return $this->isAjax($request)
                    ? $this->jsonError('Publication not found', 404)
                    : $this->redirectToRouteWithFlash('error', 'Publication not found', 'landing_service');
            }

            $comment = new Comment();
            $comment->setAuthor($this->getUser());
            $comment->setPublication($publication);
            $comment->setDateComment(new \DateTime());

            $form = $this->createForm(FrontendCommentType::class, $comment, [
                'csrf_token_id' => 'comment' . $publication->getIdPublication(),
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $logger->debug('Comment form submitted', ['form_data' => $request->request->all()]);

                if ($form->isValid()) {
                    $filterResult = $badWordFilterService->filterText($comment->getContent());
                    $logger->debug('Bad word filter result', ['filter_result' => $filterResult]);

                    if (isset($filterResult['error'])) {
                        $logger->error('Bad word filter service failed', [
                            'content' => $comment->getContent(),
                            'error' => $filterResult['error'],
                        ]);
                        $errorMessage = 'Unable to process comment due to filtering service failure. Please try again later.';

                        if ($this->isAjax($request)) {
                            return $this->jsonError($errorMessage, 503);
                        }

                        $this->addFlash('error', $errorMessage);
                        return $this->render('comment/new.html.twig', [
                            'comment' => $comment,
                            'form' => $form->createView(),
                        ]);
                    }

                    if ($filterResult['is-bad']) {
                        $logger->warning('Comment contains bad words', [
                            'bad_words' => $filterResult['bad-words'],
                            'content' => $comment->getContent(),
                        ]);
                        $errorMessage = 'Your comment contains inappropriate words: ' . implode(', ', $filterResult['bad-words']);

                        if ($this->isAjax($request)) {
                            return $this->jsonError($errorMessage, 400);
                        }

                        $this->addFlash('error', $errorMessage);
                        return $this->render('comment/new.html.twig', [
                            'comment' => $comment,
                            'form' => $form->createView(),
                        ]);
                    }

                    $logger->debug('Comment form is valid', ['comment' => [
                        'content' => $comment->getContent(),
                        'publication_id' => $publication->getIdPublication(),
                        'author_id' => $this->getUser()->getId(),
                    ]]);

                    $entityManager->persist($comment);
                    $entityManager->flush();

                    $logger->info('Comment created successfully', [
                        'id_comment' => $comment->getIdComment(),
                        'publication_id' => $publication->getIdPublication(),
                        'user_id' => $this->getUser()->getId(),
                    ]);

                    // SMS Notification
                    $publicationAuthor = $publication->getAuthor();
                    $phone = $publicationAuthor->getNumTel();
                    $smsSent = false;
                    $phoneMissing = false;

                    $logger->debug('Attempting SMS for publication author: ' . $publicationAuthor->getEmail() . ', phone: ' . ($phone ?? 'null'));

                    if ($phone && is_string($phone) && preg_match('/^\d{8}$/', $phone)) {
                        $normalizedPhone = '+216' . ltrim($phone, '0');
                        $logger->debug('Normalized phone for SMS: ' . $normalizedPhone);
                        try {
                            $smsSent = $smsService->sendSms(
                                $normalizedPhone,
                                "Someone commented on your post '{$publication->getTitle()}'!"
                            );
                            if ($smsSent) {
                                $logger->info('SMS successfully sent to ' . $normalizedPhone . ' for comment on publication ' . $publication->getIdPublication());
                            } else {
                                $this->addFlash('warning', 'Comment added, but failed to send SMS notification to publication author.');
                                $logger->warning('SMS sending failed for ' . $normalizedPhone . ': No specific error returned');
                            }
                        } catch (\Exception $e) {
                            $this->addFlash('warning', 'Comment added, but failed to send SMS notification: ' . $e->getMessage());
                            $logger->error('SMS sending failed for ' . $normalizedPhone . ': ' . $e->getMessage());
                        }
                    } else {
                        $phoneMissing = true;
                        $this->addFlash('warning', 'Comment added, but no valid phone number found for publication author.');
                        $logger->warning('Invalid or missing phone number for user ' . $publicationAuthor->getEmail() . ' (num_tel: ' . ($phone ?? 'null') . ')');
                    }

                    if ($this->isAjax($request)) {
                        return $this->jsonSuccess([
                            'smsSent' => $smsSent,
                            'phoneMissing' => $phoneMissing,
                            'data' => [
                                'id' => $comment->getIdComment(),
                                'content' => $comment->getContent(),
                                'author' => [
                                    'nom' => $comment->getAuthor()->getNom() ?? 'Anonymous',
                                    'email' => $comment->getAuthor()->getEmail() ?? '',
                                ],
                                'date_comment' => $comment->getDateComment()->format('M j, Y'),
                            ],
                        ]);
                    }

                    return $this->redirectToRouteWithFlash('success', 'Comment added successfully!', 'landing_service');
                }

                // Form validation failed
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }

                $logger->warning('Comment form validation failed', ['errors' => $errors]);

                if ($this->isAjax($request)) {
                    return $this->jsonError(implode(', ', $errors), 400);
                }

                $this->addFlash('error', implode(', ', $errors));
                return $this->redirectToRoute('landing_service');
            }

            return $this->render('comment/new.html.twig', [
                'comment' => $comment,
                'form' => $form->createView(),
            ]);
        } catch (\Exception $e) {
            $logger->error('Error in comment new: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'publication_id' => $publication ? $publication->getIdPublication() : null,
                'user_id' => $this->getUser() ? $this->getUser()->getId() : null,
                'request_data' => $request->request->all(),
            ]);

            return $this->isAjax($request)
                ? $this->jsonError('Server error: ' . $e->getMessage(), 500)
                : $this->redirectToRouteWithFlash('error', 'Failed to post comment: ' . $e->getMessage(), 'landing_service');
        }
    }
    #[Route('/comment/index/{publicationId?}', name: 'app_comment_index', methods: ['GET'])]
    public function index(
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        Request $request,
        ?int $publicationId = null
    ): Response {
        $commentRepository = $entityManager->getRepository(Comment::class);
    
        // Create a query builder for comments
        $queryBuilder = $commentRepository->createQueryBuilder('c')
            ->orderBy('c.date_comment', 'DESC');
    
        if ($publicationId) {
            $queryBuilder->where('c.publication = :publicationId')
                ->setParameter('publicationId', $publicationId);
        }
    
        // Paginate the query
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(), // Query to paginate
            $request->query->getInt('page', 1), // Current page number, default 1
            10 // Number of items per page
        );
    
        $publication = $publicationId
            ? $entityManager->getRepository(Publication::class)->find($publicationId)
            : null;
    
        return $this->render('comment/index.html.twig', [
            'pagination' => $pagination,
            'publication' => $publication,
        ]);
    }

    #[Route('/comment/{id}', name: 'app_comment_show', methods: ['GET'])]
    public function show(Comment $comment): Response
    {
        return $this->render('comment/show.html.twig', [
            'comment' => $comment,
        ]);
    }

    #[Route('/comment/{id}/edit', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $entityManager, BadWordFilterService $badWordFilterService, LoggerInterface $logger): Response
    {
        // Create form with CommentType
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Apply bad word filter
            $filterResult = $badWordFilterService->filterText($comment->getContent());
            $logger->debug('Bad word filter result for edit', ['filter_result' => $filterResult]);

            if ($filterResult['is-bad']) {
                $logger->warning('Edited comment contains bad words', [
                    'bad_words' => $filterResult['bad-words'],
                    'content' => $comment->getContent(),
                ]);
                $this->addFlash('error', 'Your comment contains inappropriate words: ' . implode(', ', $filterResult['bad-words']));
                return $this->render('comment/edit.html.twig', [
                    'comment' => $comment,
                    'form' => $form->createView(),
                ]);
            }

            // Save changes
            $entityManager->flush();
            $this->addFlash('success', 'Comment updated successfully!');
            return $this->redirectToRoute('app_comment_index');
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/comment/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        

        if ($this->isCsrfTokenValid('delete' . $comment->getIdComment(), $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Comment deleted successfully!');
        }

        return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
    }

 
    private function isAjax(Request $request): bool
    {
        return $request->isXmlHttpRequest();
    }

    private function jsonSuccess(array $data = []): JsonResponse
    {
        return new JsonResponse(array_merge(['success' => true], $data));
    }

    private function jsonError(string $message, int $status = 400): JsonResponse
    {
        return new JsonResponse(['success' => false, 'message' => $message], $status);
    }

    private function redirectToRouteWithFlash(string $type, string $message, string $route, array $params = []): Response
    {
        $this->addFlash($type, $message);
        return $this->redirectToRoute($route, $params, Response::HTTP_SEE_OTHER);
    }
    #[Route('/comments/download-pdf', name: 'download_comments_pdf', methods: ['GET'])]
    public function downloadCommentsPDF(CommentRepository $commentRepository): Response
    {
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Carint Admin');
        $pdf->SetTitle('Comments Report');
        $pdf->SetSubject('All Comments');
        $pdf->SetKeywords('Comments, PDF, Report');

        // Set default header data
        $pdf->SetHeaderData('', 0, 'Comments Report', 'Generated on ' . date('Y-m-d H:i:s'));

        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // Set font
        $pdf->SetFont('helvetica', '', 12);

        // Add a page
        $pdf->AddPage();

        // Get all comments
        $comments = $commentRepository->findAll();

        // HTML content for PDF
        $html = '<h1>Comments Report</h1>';
        $html .= '<table border="1" cellpadding="4">';
        $html .= '<tr style="background-color:#f0f0f0;">
                    <th>ID</th>
                    <th>Content</th>
                    <th>Author</th>
                    <th>Date</th>
                    <th>Publication</th>
                  </tr>';

        foreach ($comments as $comment) {
            $html .= '<tr>';
            $html .= '<td>' . $comment->getIdComment() . '</td>';
            $html .= '<td>' . htmlspecialchars($comment->getContent()) . '</td>';
            $html .= '<td>' . ($comment->getAuthor() ? htmlspecialchars($comment->getAuthor()->getNom() ?: $comment->getAuthor()->getEmail()) : 'N/A') . '</td>';
            $html .= '<td>' . ($comment->getDateComment() ? $comment->getDateComment()->format('Y-m-d H:i:s') : 'N/A') . '</td>';
            $html .= '<td>' . ($comment->getPublication() ? htmlspecialchars($comment->getPublication()->getTitle()) : 'N/A') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        // Write HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        // Clean output buffer and output PDF
        ob_clean();
        $pdf->Output('comments_report.pdf', 'D');

        return new Response();
    }

    #[Route('/comments/download-single-pdf/{id}', name: 'download_single_comment_pdf', methods: ['GET'])]
    public function downloadSingleCommentPDF(int $id, CommentRepository $commentRepository): Response
    {
        // Fetch the comment
        $comment = $commentRepository->find($id);

        if (!$comment) {
            throw $this->createNotFoundException('Comment not found');
        }

        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Carint Admin');
        $pdf->SetTitle('Comment #' . $comment->getIdComment());
        $pdf->SetSubject('Single Comment');
        $pdf->SetKeywords('Comment, PDF, Report');

        // Set default header data
        $pdf->SetHeaderData('', 0, 'Comment #' . $comment->getIdComment(), 'Generated on ' . date('Y-m-d H:i:s'));

        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // Set font
        $pdf->SetFont('helvetica', '', 12);

        // Add a page
        $pdf->AddPage();

        // HTML content for PDF
        $html = '<h1>Comment Details</h1>';
        $html .= '<table border="1" cellpadding="4">';
        $html .= '<tr><th>ID</th><td>' . $comment->getIdComment() . '</td></tr>';
        $html .= '<tr><th>Content</th><td>' . htmlspecialchars($comment->getContent()) . '</td></tr>';
        $html .= '<tr><th>Author</th><td>' . ($comment->getAuthor() ? htmlspecialchars($comment->getAuthor()->getNom() ?: $comment->getAuthor()->getEmail()) : 'N/A') . '</td></tr>';
        $html .= '<tr><th>Date</th><td>' . ($comment->getDateComment() ? $comment->getDateComment()->format('Y-m-d H:i:s') : 'N/A') . '</td></tr>';
        $html .= '<tr><th>Publication</th><td>' . ($comment->getPublication() ? htmlspecialchars($comment->getPublication()->getTitle()) : 'N/A') . '</td></tr>';
        $html .= '</table>';

        // Write HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        // Clean output buffer and output PDF
        ob_clean();
        $pdf->Output('comment_' . $comment->getIdComment() . '.pdf', 'D');

        return new Response();
    }
}