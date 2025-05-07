<?php

namespace App\Repository;

use App\Entity\Like;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Like::class);
    }

    /**
     * Find likes by a specific user and publication.
     */
    public function findByUserAndPublication(int $userId, int $publicationId): ?Like
    {
        return $this->findOneBy([
            'user' => $userId,
            'publication' => $publicationId,
        ]);
    }
}