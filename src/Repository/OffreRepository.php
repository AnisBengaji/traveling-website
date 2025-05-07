<?php

namespace App\Repository;

use App\Entity\Offre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OffreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offre::class);
    }

    // Exemple de méthode personnalisée
    public function findByDestination(string $destination): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.destination = :destination')
            ->setParameter('destination', $destination)
            ->getQuery()
            ->getResult();
    }
}