<?php

declare(strict_types=1);

namespace App\Entity;

use App\ValueObject\Geo;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Dunglas\DoctrineJsonOdm\Type\JsonbDocumentType;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Uid\Uuid;

#[Entity]
class Spot
{
    #[Id]
    #[GeneratedValue(strategy: 'NONE')]
    #[Column(type: UuidType::NAME)]
    public private(set) Uuid $id;

    #[Column]
    public string $name {
        get => $this->name;
        set {
            $this->name = $value;
            $this->slug = new AsciiSlugger()->slug($value)->lower()->toString();
        }
    }

    #[Column(unique: true)]
    public private(set) string $slug;

    #[Column(type: JsonbDocumentType::NAME)]
    public private(set) Geo $position;

    private function __construct(string $name, Geo $position)
    {
        $this->id = Uuid::v7();
        $this->name = $name;
        $this->slug = new AsciiSlugger()->slug($name)->lower()->toString();
        $this->position = $position;
    }

    public static function create(string $name, Geo $position): self
    {
        return new self($name, $position);
    }
}
