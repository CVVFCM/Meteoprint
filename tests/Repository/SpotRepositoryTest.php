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
        $repository->save(Spot::create('CVVFCM', 'cvvfcm', new Geo(49.87, 4.60), SpotType::FFV_CLUB, postcode: '08170'));
        $repository->save(Spot::create('Bairon Nautic Club', 'bairon-nautic-club', new Geo(49.60, 4.70), SpotType::FFV_CLUB, postcode: '08380'));
        $repository->save(Spot::create('SNEH', 'sneh', new Geo(16.24, -61.53), SpotType::FFV_CLUB, postcode: '97110'));

        $found = $repository->search('cvvfcm');
        self::assertSame(
            ['CVVFCM'],
            array_map(static fn (Spot $s): string => $s->name, $found),
        );

        $bySlug = $repository->findOneBySlug('bairon-nautic-club');
        self::assertNotNull($bySlug);
        self::assertSame('Bairon Nautic Club', $bySlug->name);
        self::assertSame('08', $bySlug->departmentCode());

        self::assertSame(
            ['SNEH'],
            array_map(
                static fn (Spot $spot): string => $spot->name,
                $repository->findByDepartmentCode('971'),
            ),
        );
        self::assertSame(['08', '971'], $repository->findAllDepartmentCodes());
    }
}
