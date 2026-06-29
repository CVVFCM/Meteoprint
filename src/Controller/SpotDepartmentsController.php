<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SpotRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class SpotDepartmentsController
{
    public function __construct(
        private SpotRepository $spots,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route(
        '/spots/departements',
        name: 'spot_departments',
        methods: [Request::METHOD_GET],
    )]
    #[Route(
        '/spots/departements.amp',
        name: 'spot_departments_amp',
        defaults: ['amp' => true],
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(bool $amp = false): Response
    {
        $departments = $this->spots->findAllDepartmentCodesWithCount();

        $template = $amp ? 'spot_departments/index.amp.html.twig' : 'spot_departments/index.html.twig';

        return new Response($this->twig->render($template, [
            'departments' => $departments,
            'amphtml' => $amp ? null : $this->urlGenerator->generate('spot_departments_amp', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]));
    }
}
