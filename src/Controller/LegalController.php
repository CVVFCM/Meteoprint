<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Static "Mentions légales" page (French legal notice, LCEN).
 */
final readonly class LegalController
{
    public function __construct(
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/mentions-legales', name: 'legal', methods: [Request::METHOD_GET])]
    #[Route('/mentions-legales.amp', name: 'legal_amp', defaults: ['amp' => true], methods: [Request::METHOD_GET])]
    public function __invoke(bool $amp = false): Response
    {
        $template = $amp ? 'legal/index.amp.html.twig' : 'legal/index.html.twig';

        return new Response($this->twig->render($template, [
            'amphtml' => $amp ? null : $this->urlGenerator->generate('legal_amp', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]));
    }
}
