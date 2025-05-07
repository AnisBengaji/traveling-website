<?php

namespace App\Repository;

use App\Entity\Dislike;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DislikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dislike::class);
    }

    /**
     * Find dislikes by a specific user and publication.
     */
    public function findByUserAndPublication(int $userId, int $publicationId): ?Dislike
    {
        return $this->findOneBy([
            'user' => $userId,
            'publication' => $publicationId,
        ]);
    }
}