<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }
    // src/Repository/EvenementRepository.php

public function searchEvenements(?string $search, ?float $prixMin, ?float $prixMax): array
{
    $qb = $this->createQueryBuilder('e');

    if ($search) {
        $qb->andWhere('e.nom LIKE :search OR e.lieu LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    if ($prixMin !== null) {
        $qb->andWhere('e.price >= :prixMin')
           ->setParameter('prixMin', $prixMin);
    }

    if ($prixMax !== null) {
        $qb->andWhere('e.price <= :prixMax')
           ->setParameter('prixMax', $prixMax);
    }

    return $qb->getQuery()->getResult();
}

}