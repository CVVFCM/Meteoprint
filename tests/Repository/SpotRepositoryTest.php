<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Spot;
use App\Entity\SpotType;
use App\Repository\SpotRepository;
use App\ValueObject\Geo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SpotRepositoryTest extends KernelTestCase
{
    public function testSaveSearchAndFindBySlug(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM '.Spot::class)->execute();

        $repository = $container->get(SpotRepository::class);
        $repository->save(Spot::create('La Rochelle Nautique', 'la-rochelle-nautique', new Geo(46.16, -1.15), SpotType::FFV_CLUB));
        $repository->save(Spot::create('Voile Lyon', 'voile-lyon', new Geo(45.76, 4.83), SpotType::FFV_CLUB));

        $found = $repository->search('rochelle');
        self::assertSame(
            ['La Rochelle Nautique'],
            array_map(static fn (Spot $s): string => $s->name, $found),
        );

        $bySlug = $repository->findOneBySlug('voile-lyon');
        self::assertNotNull($bySlug);
        self::assertSame('Voile Lyon', $bySlug->name);
    }
}
