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

    #[Column(length: 5, nullable: true)]
    public private(set) ?string $postcode = null;

    private function __construct(
        string $name,
        string $slug,
        Geo $position,
        SpotType $type,
        ?FFVClubId $ffvClubId = null,
        ?string $postcode = null,
    ) {
        $this->id = Uuid::v7();
        $this->name = $name;
        $this->slug = $slug;
        $this->position = $position;
        $this->type = $type;
        $this->ffvClubId = $ffvClubId;
        $this->postcode = self::normalizePostcode($postcode);
    }

    public static function create(
        string $name,
        string $slug,
        Geo $position,
        SpotType $type,
        ?FFVClubId $ffvClubId = null,
        ?string $postcode = null,
    ): self {
        return new self($name, $slug, $position, $type, $ffvClubId, $postcode);
    }

    public function update(string $name, Geo $position, ?string $slug = null, ?string $postcode = null): void
    {
        $this->name = $name;
        $this->position = $position;
        $this->postcode = self::normalizePostcode($postcode);
        if (null !== $slug) {
            $this->slug = $slug;
        }
    }

    public function relocate(Geo $position): void
    {
        $this->position = $position;
    }

    public function departmentCode(): ?string
    {
        return self::departmentCodeFromPostcode($this->postcode);
    }

    public static function departmentCodeFromPostcode(?string $postcode): ?string
    {
        $normalized = self::normalizePostcode($postcode);
        if (null === $normalized) {
            return null;
        }

        return str_starts_with($normalized, '97')
            ? substr($normalized, 0, 3)
            : substr($normalized, 0, 2);
    }

    private static function normalizePostcode(?string $postcode): ?string
    {
        if (null === $postcode) {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', trim($postcode));
        if (null === $normalized || 1 !== preg_match('/^\d{5}$/', $normalized)) {
            return null;
        }

        return $normalized;
    }
}
