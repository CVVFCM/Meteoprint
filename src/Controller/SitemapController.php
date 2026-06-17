<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Spot;
use App\Repository\SpotRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class SitemapController
{
    private const int CACHE_TTL = 3600;

    public function __construct(
        private SpotRepository $spots,
        private UrlGeneratorInterface $urlGenerator,
        private Environment $twig,
    ) {
    }

    #[Route('/sitemap.xml', name: 'sitemap', methods: [Request::METHOD_GET])]
    public function __invoke(): Response
    {
        /** @var list<string> $spotUrls */
        $spotUrls = array_map(
            fn (Spot $spot): string => $this->urlGenerator->generate('forecast_spot', ['slug' => $spot->slug], UrlGeneratorInterface::ABSOLUTE_URL),
            $this->spots->findAllOrderedBySlug(),
        );

        $response = new Response($this->twig->render('seo/sitemap.xml.twig', [
            'homepageUrl' => $this->urlGenerator->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'spotUrls' => $spotUrls,
        ]));
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->setPublic();
        $response->setMaxAge(self::CACHE_TTL);
        $response->setSharedMaxAge(self::CACHE_TTL);

        return $response;
    }
}
