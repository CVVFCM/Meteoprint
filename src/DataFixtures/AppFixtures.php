<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Bridge\Ffvoile\FFVClubId;
use App\Entity\Spot;
use App\Entity\SpotType;
use App\ValueObject\Geo;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $cvvfcm = Spot::create('CVVFCM', 'cvvfcm', new Geo(49.87, 4.6), SpotType::FFV_CLUB, new FFVClubId('08002'), '08500');
        $manager->persist($cvvfcm);

        $manager->flush();
    }
}
