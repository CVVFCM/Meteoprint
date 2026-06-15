<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Forecast;
use App\ValueObject\Geo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Forecast>
 */
final class ForecastRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Forecast::class);
    }

    public function findOneForDay(Geo $position, \DateTimeImmutable $day): ?Forecast
    {
        return $this->findOneBy([
            'position' => $position,
            'day' => $day,
        ]);
    }

    public function save(Forecast $forecast): void
    {
        $em = $this->getEntityManager();
        $em->persist($forecast);
        $em->flush();
    }
}
