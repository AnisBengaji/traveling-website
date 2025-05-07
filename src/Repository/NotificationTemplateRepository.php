<?php

namespace App\Repository;

use App\Entity\NotificationTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationTemplate::class);
    }

    public function findOneByType(string $type): ?NotificationTemplate
    {
        return $this->findOneBy(['type' => $type]);
    }
} 