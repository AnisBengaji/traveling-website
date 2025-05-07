<?php

namespace App\Repository;

use App\Entity\Publication;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<Publication>
 */
class PublicationRepository extends ServiceEntityRepository
{
    private $logger;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, Publication::class);
        $this->logger = $logger;
    }

    public function findByFiltered(array $criteria, ?array $orderBy = null, $user = null): array
    {
        $qb = $this->createQueryBuilder('p');
        foreach ($criteria as $field => $value) {
            $qb->andWhere("p.$field = :$field")->setParameter($field, $value);
        }
        if ($user) {
            $qb->andWhere('p.author NOT IN (SELECT bu.id FROM App\Entity\User bu JOIN bu.blockedByUsers bbu WHERE bbu = :user)')
               ->setParameter('user', $user);
        }
        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->orderBy("p.$field", $direction);
            }
        }
        return $qb->getQuery()->getResult();
    }

    public function getPublicationsByMonth(): array
    {
        $now = new \DateTime();
        $startDate = (clone $now)->modify('-11 months')->format('Y-m-01');
        
        $qb = $this->createQueryBuilder('p')
            ->select('CONCAT(YEAR(p.datePublication), "-", LPAD(MONTH(p.datePublication), 2, "0")) as month, COUNT(p.idPublication) as count')
            ->where('p.datePublication >= :startDate')
            ->setParameter('startDate', $startDate)
            ->groupBy('month')
            ->orderBy('month', 'ASC');

        $results = $qb->getQuery()->getArrayResult();

        $data = [];
        $labels = [];
        $current = new \DateTime($startDate);
        while ($current <= $now) {
            $monthKey = $current->format('Y-m');
            $labels[] = $current->format('M Y');
            $data[$monthKey] = 0;
            $current->modify('+1 month');
        }

        foreach ($results as $result) {
            $data[$result['month']] = (int) $result['count'];
        }

        return [
            'labels' => $labels,
            'data' => array_values($data),
        ];
    }

    public function findPublicationsWithLikesAndDislikes(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p')
            ->leftJoin('p.likes', 'l')
            ->leftJoin('p.dislikes', 'd')
            ->addOrderBy('p.datePublication', 'DESC');

        if ($user) {
            $qb->addSelect('CASE WHEN EXISTS (
                    SELECT 1 FROM App\Entity\Like l2 WHERE l2.publication = p AND l2.user = :user
                ) THEN 1 ELSE 0 END AS HIDDEN isLiked')
               ->addSelect('CASE WHEN EXISTS (
                    SELECT 1 FROM App\Entity\Dislike d2 WHERE d2.publication = p AND d2.user = :user
                ) THEN 1 ELSE 0 END AS HIDDEN isDisliked')
               ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByCategory(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('c.nomCategory AS category, COUNT(p.id_publication) AS count')
            ->join('p.category', 'c')
            ->groupBy('c.nomCategory')
            ->orderBy('count', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function countByComments(): array
{
    $qb = $this->createQueryBuilder('p')
        ->select('p.title AS publication, COUNT(c.id_comment) AS count')
        ->leftJoin('p.comments', 'c')
        ->groupBy('p.id_publication')
        ->orderBy('count', 'DESC');

    return $qb->getQuery()->getResult();
}


public function findAllWithRelations()
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.author', 'a')
            ->leftJoin('p.comments', 'cm')
            ->leftJoin('p.likes', 'l')
            ->leftJoin('p.dislikes', 'd')
            ->addSelect('c', 'a', 'cm', 'l', 'd')
            ->orderBy('p.datePublication', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function searchByTitleOrContent(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.title LIKE :query OR p.contenu LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.datePublication', 'DESC')
            ->getQuery()
            ->getResult();
    }
}