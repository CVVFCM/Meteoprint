<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class RobotsController
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private Environment $twig,
    ) {
    }

    #[Route('/robots.txt', name: 'robots', methods: [Request::METHOD_GET])]
    public function __invoke(): Response
    {
        $response = new Response($this->twig->render('seo/robots.txt.twig', [
            'sitemapUrl' => $this->urlGenerator->generate('sitemap', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]));
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');

        return $response;
    }
}
