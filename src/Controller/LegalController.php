<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

/**
 * Static "Mentions légales" page (French legal notice, LCEN).
 */
final readonly class LegalController
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    #[Route('/mentions-legales', name: 'legal', methods: [Request::METHOD_GET])]
    public function __invoke(): Response
    {
        return new Response($this->twig->render('legal/index.html.twig'));
    }
}
