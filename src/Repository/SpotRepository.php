<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Spot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Spot>
 */
final class SpotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Spot::class);
    }

    public function findOneBySlug(string $slug): ?Spot
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * @return list<Spot>
     */
    public function findAllOrderedBySlug(): array
    {
        /** @var list<Spot> $result */
        $result = $this->createQueryBuilder('s')
            ->orderBy('s.slug', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Case-insensitive "contains" search on the spot name.
     *
     * @return list<Spot>
     */
    public function search(string $query, int $limit = 5): array
    {
        /** @var list<Spot> $result */
        $result = $this->createQueryBuilder('s')
            ->where('LOWER(s.name) LIKE LOWER(:q)')
            ->setParameter('q', '%'.$query.'%')
            ->orderBy('s.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function save(Spot $spot, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($spot);
        if ($flush) {
            $em->flush();
        }
    }
}
