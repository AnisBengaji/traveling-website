<?php

namespace App\Repository;

use App\Entity\Tutorial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tutorial>
 */
class TutorialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tutorial::class);
    }

    /**
     * Récupère tous les tutoriels, triés par date de début.
     *
     * @return Tutorial[]
     */
    public function findAllOrderedByDateDebut(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les tutoriels par offre.
     *
     * @param string $offre
     * @return Tutorial[]
     */
    public function findByOffre(string $offre): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.offre = :offre')
            ->setParameter('offre', $offre)
            ->orderBy('t.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les tutoriels dont la date de début est supérieure à une date donnée.
     *
     * @param \DateTimeInterface $date
     * @return Tutorial[]
     */
    public function findByDateDebutAfter(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.dateDebut > :date')
            ->setParameter('date', $date)
            ->orderBy('t.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les tutoriels dans une plage de prix.
     *
     * @param float $minPrice
     * @param float $maxPrice
     * @return Tutorial[]
     */
    public function findByPriceRange(float $minPrice, float $maxPrice): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.prixTutorial BETWEEN :minPrice AND :maxPrice')
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->orderBy('t.prixTutorial', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un tutoriel par son nom exact.
     *
     * @param string $nomTutorial
     * @return Tutorial|null
     */
    public function findOneByNomTutorial(string $nomTutorial): ?Tutorial
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.nomTutorial = :nom')
            ->setParameter('nom', $nomTutorial)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte le nombre total de tutoriels.
     *
     * @return int
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}