<?php

declare(strict_types=1);

namespace App\Entity;

use App\Bridge\Ffvoile\FFVClubId;
use App\Repository\SpotRepository;
use App\ValueObject\Geo;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Dunglas\DoctrineJsonOdm\Type\JsonbDocumentType;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[Entity(repositoryClass: SpotRepository::class)]
#[UniqueConstraint(name: 'spot_ffv_club_lookup', fields: ['ffvClubId'])]
class Spot
{
    #[Id]
    #[GeneratedValue(strategy: 'NONE')]
    #[Column(type: UuidType::NAME)]
    public private(set) Uuid $id;

    #[Column]
    public private(set) string $name;

    #[Column(unique: true)]
    public private(set) string $slug;

    #[Column(type: JsonbDocumentType::NAME)]
    public private(set) Geo $position;

    #[Column(enumType: SpotType::class)]
    public private(set) SpotType $type;

    #[Column(type: JsonbDocumentType::NAME, nullable: true, name: 'ffvClubId')]
    public private(set) ?FFVClubId $ffvClubId = null;

    private function __construct(
        string $name,
        string $slug,
        Geo $position,
        SpotType $type,
        ?FFVClubId $ffvClubId = null,
    ) {
        $this->id = Uuid::v7();
        $this->name = $name;
        $this->slug = $slug;
        $this->position = $position;
        $this->type = $type;
        $this->ffvClubId = $ffvClubId;
    }

    public static function create(
        string $name,
        string $slug,
        Geo $position,
        SpotType $type,
        ?FFVClubId $ffvClubId = null,
    ): self {
        return new self($name, $slug, $position, $type, $ffvClubId);
    }

    public function update(string $name, Geo $position, ?string $slug = null): void
    {
        $this->name = $name;
        $this->position = $position;
        if (null !== $slug) {
            $this->slug = $slug;
        }
    }

    public function relocate(Geo $position): void
    {
        $this->position = $position;
    }
}
