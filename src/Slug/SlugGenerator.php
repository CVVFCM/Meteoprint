<?php

declare(strict_types=1);

namespace App\Slug;

use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Generates a slug from a text, appending a numeric suffix when the candidate is already
 * taken (`foo-bar`, `foo-bar-2`, `foo-bar-3`, …). The caller decides what "taken" means
 * (in-memory set, database lookup, …), so the service stays stateless and reusable.
 */
final readonly class SlugGenerator
{
    public function __construct(
        private SluggerInterface $slugger,
    ) {
    }

    /**
     * @param callable(string): bool $isTaken
     */
    public function generate(string $text, callable $isTaken): string
    {
        $base = $this->slugger->slug($text)->lower()->toString();
        $slug = $base;
        $suffix = 1;

        while ($isTaken($slug)) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
