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
     * @return list<string>
     */
    public function findAllDepartmentCodes(): array
    {
        /** @var list<array{postcode: ?string}> $rows */
        $rows = $this->createQueryBuilder('s')
            ->select('s.postcode AS postcode')
            ->andWhere('s.postcode IS NOT NULL')
            ->orderBy('s.postcode', 'ASC')
            ->getQuery()
            ->getArrayResult();

        /** @var list<string> $codes */
        $codes = [];
        foreach ($rows as ['postcode' => $postcode]) {
            $code = Spot::departmentCodeFromPostcode($postcode);
            if (null !== $code && !\in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    /**
     * @return list<array{code: string, count: int, name: string}>
     */
    public function findAllDepartmentCodesWithCount(): array
    {
        /** @var list<array{postcode: ?string}> $rows */
        $rows = $this->createQueryBuilder('s')
            ->select('s.postcode AS postcode')
            ->andWhere('s.postcode IS NOT NULL')
            ->orderBy('s.postcode', 'ASC')
            ->getQuery()
            ->getArrayResult();

        /** @var array<string, int> $departmentCounts */
        $departmentCounts = [];
        foreach ($rows as ['postcode' => $postcode]) {
            $code = Spot::departmentCodeFromPostcode($postcode);
            if (null !== $code) {
                $departmentCounts[$code] = ($departmentCounts[$code] ?? 0) + 1;
            }
        }

        /** @var list<array{code: string, count: int, name: string}> $result */
        $result = [];
        foreach ($departmentCounts as $code => $count) {
            $result[] = [
                'code' => $code,
                'count' => $count,
                'name' => $this->getDepartmentName($code),
            ];
        }

        usort($result, static fn (array $a, array $b): int => $a['code'] <=> $b['code']);

        return $result;
    }

    /**
     * Returns the French department name for a given code.
     */
    private function getDepartmentName(string|int $code): string
    {
        $codeString = \is_int($code) ? sprintf('%02d', $code) : $code;

        /** @var array<string, string> $names */
        $names = [
            '01' => 'Ain',
            '02' => 'Aisne',
            '03' => 'Allier',
            '04' => 'Alpes-de-Haute-Provence',
            '05' => 'Hautes-Alpes',
            '06' => 'Alpes-Maritimes',
            '07' => 'Ardèche',
            '08' => 'Ardennes',
            '09' => 'Ariège',
            '10' => 'Aube',
            '11' => 'Aude',
            '12' => 'Aveyron',
            '13' => 'Bouches-du-Rhône',
            '14' => 'Calvados',
            '15' => 'Cantal',
            '16' => 'Charente',
            '17' => 'Charente-Maritime',
            '18' => 'Cher',
            '19' => 'Corrèze',
            '2A' => 'Corse-du-Sud',
            '2B' => 'Haute-Corse',
            '21' => 'Côte-d\'Or',
            '22' => 'Côtes-d\'Armor',
            '23' => 'Creuse',
            '24' => 'Dordogne',
            '25' => 'Doubs',
            '26' => 'Drôme',
            '27' => 'Eure',
            '28' => 'Eure-et-Loir',
            '29' => 'Finistère',
            '30' => 'Gard',
            '31' => 'Haute-Garonne',
            '32' => 'Gers',
            '33' => 'Gironde',
            '34' => 'Hérault',
            '35' => 'Ille-et-Vilaine',
            '36' => 'Indre',
            '37' => 'Indre-et-Loire',
            '38' => 'Isère',
            '39' => 'Jura',
            '40' => 'Landes',
            '41' => 'Loir-et-Cher',
            '42' => 'Loire',
            '43' => 'Haute-Loire',
            '44' => 'Loire-Atlantique',
            '45' => 'Loiret',
            '46' => 'Lot',
            '47' => 'Lot-et-Garonne',
            '48' => 'Lozère',
            '49' => 'Maine-et-Loire',
            '50' => 'Manche',
            '51' => 'Marne',
            '52' => 'Haute-Marne',
            '53' => 'Mayenne',
            '54' => 'Meurthe-et-Moselle',
            '55' => 'Meuse',
            '56' => 'Morbihan',
            '57' => 'Moselle',
            '58' => 'Nièvre',
            '59' => 'Nord',
            '60' => 'Oise',
            '61' => 'Orne',
            '62' => 'Pas-de-Calais',
            '63' => 'Puy-de-Dôme',
            '64' => 'Pyrénées-Atlantiques',
            '65' => 'Hautes-Pyrénées',
            '66' => 'Pyrénées-Orientales',
            '67' => 'Bas-Rhin',
            '68' => 'Haut-Rhin',
            '69' => 'Rhône',
            '70' => 'Haute-Saône',
            '71' => 'Saône-et-Loire',
            '72' => 'Sarthe',
            '73' => 'Savoie',
            '74' => 'Haute-Savoie',
            '75' => 'Paris',
            '76' => 'Seine-Maritime',
            '77' => 'Seine-et-Marne',
            '78' => 'Yvelines',
            '79' => 'Deux-Sèvres',
            '80' => 'Somme',
            '81' => 'Tarn',
            '82' => 'Tarn-et-Garonne',
            '83' => 'Var',
            '84' => 'Vaucluse',
            '85' => 'Vendée',
            '86' => 'Vienne',
            '87' => 'Haute-Vienne',
            '88' => 'Vosges',
            '89' => 'Yonne',
            '90' => 'Territoire de Belfort',
            '91' => 'Essonne',
            '92' => 'Hauts-de-Seine',
            '93' => 'Seine-Saint-Denis',
            '94' => 'Val-de-Marne',
            '95' => 'Val-d\'Oise',
            '971' => 'Guadeloupe',
            '972' => 'Martinique',
            '973' => 'Guyane',
            '974' => 'La Réunion',
            '976' => 'Mayotte',
        ];

        return $names[$codeString] ?? $codeString;
    }

    /**
     * @return list<Spot>
     */
    public function findByDepartmentCode(string $departmentCode): array
    {
        /** @var list<Spot> $result */
        $result = $this->createQueryBuilder('s')
            ->andWhere('s.postcode LIKE :prefix')
            ->setParameter('prefix', $departmentCode.'%')
            ->orderBy('s.name', 'ASC')
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
