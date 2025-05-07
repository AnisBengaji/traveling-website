<?php
namespace App\Controller;
use App\Entity\Like;
use App\Entity\Dislike;
use App\Entity\Publication;
use App\Repository\LikeRepository;
use App\Repository\DislikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
class LikeDislikeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LikeRepository $likeRepository,
        private DislikeRepository $dislikeRepository
    ) {}
    #[Route('/like-post/{id}', name: 'like_post', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENT')] // Changed from ROLE_USER to ROLE_CLIENT
    public function likePost(Request $request, Publication $publication): JsonResponse
    {
        if (!$this->isCsrfTokenValid('like_dislike', $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }
        if ($publication->getVisibility() !== 'public' && $publication->getAuthor() !== $user) {
            return $this->json(['success' => false, 'message' => 'Unauthorized to interact with this post'], 403);
        }
        $data = json_decode($request->getContent(), true);
        $liked = $data['liked'] ?? false;
        $existingLike = $this->likeRepository->findOneBy(['publication' => $publication, 'user' => $user]);
        $existingDislike = $this->dislikeRepository->findOneBy(['publication' => $publication, 'user' => $user]);
        if ($liked) {
            if (!$existingLike) {
                $like = new Like();
                $like->setPublication($publication);
                $like->setUser($user);
                $this->entityManager->persist($like);
            }
            if ($existingDislike) {
                $this->entityManager->remove($existingDislike);
            }
        } else {
            if ($existingLike) {
                $this->entityManager->remove($existingLike);
            }
        }
        $this->entityManager->flush();
        return $this->json([
            'success' => true,
            'likes' => count($publication->getLikes()),
            'dislikes' => count($publication->getDislikes()),
            'liked' => $liked && !$existingLike,
            'disliked' => (bool) $this->dislikeRepository->findOneBy(['publication' => $publication, 'user' => $user])
        ]);
    }
    #[Route('/dislike-post/{id}', name: 'dislike_post', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENT')] // Changed from ROLE_USER to ROLE_CLIENT
    public function dislikePost(Request $request, Publication $publication): JsonResponse
    {
        if (!$this->isCsrfTokenValid('like_dislike', $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }
        if ($publication->getVisibility() !== 'public' && $publication->getAuthor() !== $user) {
            return $this->json(['success' => false, 'message' => 'Unauthorized to interact with this post'], 403);
        }
        $data = json_decode($request->getContent(), true);
        $disliked = $data['disliked'] ?? false;
        $existingLike = $this->likeRepository->findOneBy(['publication' => $publication, 'user' => $user]);
        $existingDislike = $this->dislikeRepository->findOneBy(['publication' => $publication, 'user' => $user]);
        if ($disliked) {
            if (!$existingDislike) {
                $dislike = new Dislike();
                $dislike->setPublication($publication);
                $dislike->setUser($user);
                $this->entityManager->persist($dislike);
            }
            if ($existingLike) {
                $this->entityManager->remove($existingLike);
            }
        } else {
            if ($existingDislike) {
                $this->entityManager->remove($existingDislike);
            }
        }
        $this->entityManager->flush();
        return $this->json([
            'success' => true,
            'likes' => count($publication->getLikes()),
            'dislikes' => count($publication->getDislikes()),
            'liked' => (bool) $this->likeRepository->findOneBy(['publication' => $publication, 'user' => $user]),
            'disliked' => $disliked && !$existingDislike
        ]);
    }
}