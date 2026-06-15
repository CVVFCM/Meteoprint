<?php

declare(strict_types=1);

namespace App\Tests\Slug;

use App\Slug\SlugGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class SlugGeneratorTest extends TestCase
{
    public function testFreeSlugIsTheBase(): void
    {
        $generator = new SlugGenerator(new AsciiSlugger());

        self::assertSame('foo-bar', $generator->generate('Foo Bar', static fn (): bool => false));
    }

    public function testSuffixesUntilFree(): void
    {
        $generator = new SlugGenerator(new AsciiSlugger());

        $taken = ['foo-bar' => true];
        self::assertSame('foo-bar-2', $generator->generate('Foo Bar', static fn (string $s): bool => isset($taken[$s])));

        $taken = ['foo-bar' => true, 'foo-bar-2' => true];
        self::assertSame('foo-bar-3', $generator->generate('Foo Bar', static fn (string $s): bool => isset($taken[$s])));
    }

    public function testAccentsAndCaseAreNormalised(): void
    {
        $generator = new SlugGenerator(new AsciiSlugger());

        self::assertSame('asptt-marseille', $generator->generate('ASPTT Marseille', static fn (): bool => false));
    }
}
