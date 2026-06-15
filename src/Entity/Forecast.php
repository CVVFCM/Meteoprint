<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ForecastRepository;
use App\ValueObject\ForecastSlot;
use App\ValueObject\Geo;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Dunglas\DoctrineJsonOdm\Type\JsonbDocumentType;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[Entity(repositoryClass: ForecastRepository::class)]
#[UniqueConstraint(columns: ['position', 'day'])]
class Forecast
{
    public const string STALE_AFTER = 'PT1H';

    #[Id]
    #[GeneratedValue(strategy: 'NONE')]
    #[Column(type: UuidType::NAME)]
    public private(set) Uuid $id;

    #[Column(type: JsonbDocumentType::NAME)]
    public private(set) Geo $position;

    #[Column(type: 'date_immutable')]
    public private(set) \DateTimeImmutable $day;

    #[Column(type: 'datetimetz_immutable')]
    public private(set) \DateTimeImmutable $generatedAt;

    /**
     * @var list<ForecastSlot>
     */
    #[Column(type: JsonbDocumentType::NAME)]
    public private(set) array $slots;

    /**
     * @param list<ForecastSlot> $slots
     */
    private function __construct(Geo $position, \DateTimeImmutable $day, array $slots, \DateTimeImmutable $generatedAt)
    {
        $this->id = Uuid::v7();
        $this->position = $position;
        $this->day = $day;
        $this->slots = $slots;
        $this->generatedAt = $generatedAt;
    }

    /**
     * @param list<ForecastSlot> $slots
     */
    public function refresh(array $slots, \DateTimeImmutable $generatedAt): void
    {
        $this->slots = $slots;
        $this->generatedAt = $generatedAt;
    }

    public function isStale(\DateTimeImmutable $now): bool
    {
        return $this->generatedAt < $now->sub(new \DateInterval(self::STALE_AFTER));
    }

    /**
     * @param list<ForecastSlot> $slots
     */
    public static function create(Geo $position, \DateTimeImmutable $day, array $slots, \DateTimeImmutable $generatedAt): self
    {
        return new self($position, $day, $slots, $generatedAt);
    }
}
