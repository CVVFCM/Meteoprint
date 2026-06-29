<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * AMP requires every stylesheet inlined in a single <style amp-custom> (no external sheets,
 * no importmap). This exposes the app's compiled CSS as a string so the AMP layout can embed
 * the exact same bytes the canonical pages load.
 */
final class AmpExtension extends AbstractExtension
{
    private const string STYLESHEET_LOGICAL_PATH = 'styles/app.css';

    public function __construct(
        private readonly AssetMapperInterface $assetMapper,
    ) {
    }

    public function getFunctions(): array
    {
        // is_safe html: the CSS is our own asset, emitted verbatim inside <style amp-custom>.
        return [
            new TwigFunction('amp_inline_styles', $this->inlineStyles(...), ['is_safe' => ['html']]),
        ];
    }

    public function inlineStyles(): string
    {
        $asset = $this->assetMapper->getAsset(self::STYLESHEET_LOGICAL_PATH);
        if (null === $asset) {
            throw new \RuntimeException(\sprintf('AMP stylesheet "%s" not found in the asset mapper.', self::STYLESHEET_LOGICAL_PATH));
        }

        // content is null when the asset is served straight from disk (no compiler rewrote it),
        // which is the case for our plain CSS.
        return $asset->content ?? (string) file_get_contents($asset->sourcePath);
    }
}
