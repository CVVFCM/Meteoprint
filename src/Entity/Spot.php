<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SpotRepository;
use App\ValueObject\Geo;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Dunglas\DoctrineJsonOdm\Type\JsonbDocumentType;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[Entity(repositoryClass: SpotRepository::class)]
class Spot
{
    #[Id]
    #[GeneratedValue(strategy: 'NONE')]
    #[Column(type: UuidType::NAME)]
    public private(set) Uuid $id;

    #[Column]
    public private(set) string $name;

    // Unique: callers build a collision-free slug via App\Slug\SlugGenerator.
    #[Column(unique: true)]
    public private(set) string $slug;

    #[Column(type: JsonbDocumentType::NAME)]
    public private(set) Geo $position;

    #[Column(enumType: SpotType::class)]
    public private(set) SpotType $type;

    private function __construct(string $name, string $slug, Geo $position, SpotType $type)
    {
        $this->id = Uuid::v7();
        $this->name = $name;
        $this->slug = $slug;
        $this->position = $position;
        $this->type = $type;
    }

    public static function create(string $name, string $slug, Geo $position, SpotType $type): self
    {
        return new self($name, $slug, $position, $type);
    }

    public function relocate(Geo $position): void
    {
        $this->position = $position;
    }
}
