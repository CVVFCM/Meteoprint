<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\PlaceSearchType;
use App\PlaceSearch\PlaceSelection;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Homepage: a single place-search autocomplete that redirects to the forecast page.
 */
final readonly class HomepageController
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/', name: 'homepage', methods: [Request::METHOD_GET])]
    public function __invoke(Request $request): Response
    {
        $form = $this->formFactory->create(PlaceSearchType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $value = $form->get('place')->getData();
            if (!\is_string($value)) {
                throw new \LogicException('The place search form must submit a string value.');
            }

            if (PlaceSelection::isSpot($value)) {
                return new RedirectResponse($this->urlGenerator->generate('forecast_spot', [
                    'slug' => PlaceSelection::spotSlug($value),
                ]));
            }

            $coordinates = PlaceSelection::coordinates($value);

            // Arome HD resolution is ~1.5 km → 2 decimals is enough and keeps the URL tidy.
            return new RedirectResponse($this->urlGenerator->generate('forecast', [
                'latitude' => round($coordinates['latitude'], 2),
                'longitude' => round($coordinates['longitude'], 2),
            ]));
        }

        // A submitted form reaching this point is invalid (the valid case redirected above).
        $status = $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK;

        return new Response($this->twig->render('homepage/index.html.twig', [
            'form' => $form->createView(),
        ]), $status);
    }
}
