<?php

declare(strict_types=1);

namespace App\Bridge\Ffvoile\Command;

use App\Bridge\Ffvoile\Club;
use App\Bridge\Ffvoile\FFVClubId;
use App\Bridge\Ffvoile\FfvoileClubScraper;
use App\Entity\Spot;
use App\Entity\SpotType;
use App\Slug\SlugGenerator;
use App\ValueObject\Geo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Fetches every FFVoile sailing club (name + coordinates) from the club map and prints
 * them, optionally writing a JSON or CSV file.
 */
#[AsCommand(
    name: 'app:ffvoile:fetch-clubs',
    description: 'Fetch all FFVoile clubs (name + lat/lng) from carto.ffvoile.fr',
)]
final class FetchFfvoileClubsCommand extends Command
{
    public function __construct(
        private readonly FfvoileClubScraper $scraper,
        private readonly SlugGenerator $slugGenerator,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dept', null, InputOption::VALUE_REQUIRED, 'Restrict to a single department code (e.g. 08, 2A, 971)')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Also write a file: json or csv', null)
            ->addOption('persist', null, InputOption::VALUE_NEGATABLE, 'Store imported clubs as Spots', true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dept = $input->getOption('dept');
        $departments = \is_string($dept) ? [$dept] : FfvoileClubScraper::DEPARTMENTS;

        /** @var array<string, Club> $clubs */
        $clubs = [];

        $io->progressStart(\count($departments));
        foreach ($departments as $code) {
            foreach ($this->scraper->fetchDepartment($code) as $club) {
                $clubs[$club->id] = $club;
            }
            $io->progressAdvance();
        }
        $io->progressFinish();

        $clubs = array_values($clubs);
        usort($clubs, static fn (Club $a, Club $b): int => $a->name <=> $b->name);

        $io->table(
            ['Id', 'Name', 'Latitude', 'Longitude', 'City'],
            array_map(static fn (Club $c): array => [
                $c->id,
                $c->name,
                \sprintf('%.5f', $c->latitude),
                \sprintf('%.5f', $c->longitude),
                $c->city ?? '',
            ], $clubs),
        );

        if (false !== $input->getOption('persist')) {
            $this->persist($clubs);
            $io->success(\sprintf('%d clubs imported as spots', \count($clubs)));
        }

        $format = $input->getOption('format');
        if (\is_string($format)) {
            $path = $this->write($format, $clubs);
            $io->success(\sprintf('%d clubs written to %s', \count($clubs), $path));
        } else {
            $io->success(\sprintf('%d clubs found', \count($clubs)));
        }

        return Command::SUCCESS;
    }

    /**
     * Upserts spots from FFVoile clubs (idempotent — safe to run multiple times).
     * Existing spots are updated in-place; new ones are created with collision-free slugs.
     *
     * @param list<Club> $clubs
     */
    private function persist(array $clubs): void
    {
        /** @var array<string, true> $existingSlugs */
        $existingSlugs = $this->fetchAllFfvoileSlugs();

        /** @var array<string, true> $used */
        $used = [];

        foreach ($clubs as $i => $club) {
            $existing = $this->em->getRepository(Spot::class)->findOneBy([
                'ffvClubId' => new FFVClubId($club->id),
            ]);

            if (null !== $existing) {
                unset($existingSlugs[$existing->slug]);

                $newSlug = $this->slugGenerator->generate(
                    $club->name,
                    fn (string $candidate): bool => isset($used[$candidate]) || isset($existingSlugs[$candidate]),
                );
                $used[$newSlug] = true;
                $existing->update($club->name, new Geo($club->latitude, $club->longitude), $newSlug);
            } else {
                $slug = $this->slugGenerator->generate(
                    $club->name,
                    fn (string $candidate): bool => isset($used[$candidate]) || isset($existingSlugs[$candidate]),
                );
                $used[$slug] = true;

                $this->em->persist(Spot::create(
                    $club->name,
                    $slug,
                    new Geo($club->latitude, $club->longitude),
                    SpotType::FFV_CLUB,
                    new FFVClubId($club->id),
                ));
            }

            if (0 === ($i + 1) % 200) {
                $this->em->flush();
            }
        }

        $this->em->flush();
    }

    /**
     * Fetches all existing FFVoile club slugs from the database.
     *
     * @return array<string, true>
     */
    private function fetchAllFfvoileSlugs(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('s.slug')
            ->from(Spot::class, 's')
            ->where('s.type = :type')
            ->setParameter('type', SpotType::FFV_CLUB);

        /** @var list<array{slug: string}> $result */
        $result = $qb->getQuery()->getArrayResult();

        /** @var array<string, true> $slugs */
        $slugs = [];
        foreach ($result as ['slug' => $slug]) {
            $slugs[$slug] = true;
        }

        return $slugs;
    }

    /**
     * @param list<Club> $clubs
     */
    private function write(string $format, array $clubs): string
    {
        $dir = \dirname(__DIR__, 2).'/var';

        if ('csv' === $format) {
            $path = $dir.'/ffvoile-clubs.csv';
            $handle = fopen($path, 'w');
            if (false === $handle) {
                throw new \RuntimeException(\sprintf('Cannot write to "%s".', $path));
            }
            fputcsv($handle, ['id', 'name', 'latitude', 'longitude', 'city'], escape: '');
            foreach ($clubs as $c) {
                fputcsv($handle, [$c->id, $c->name, $c->latitude, $c->longitude, $c->city], escape: '');
            }
            fclose($handle);

            return $path;
        }

        if ('json' === $format) {
            $path = $dir.'/ffvoile-clubs.json';
            file_put_contents($path, json_encode($clubs, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR));

            return $path;
        }

        throw new \InvalidArgumentException(\sprintf('Unknown format "%s" (use json or csv).', $format));
    }
}
