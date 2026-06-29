<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SpotRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class SpotDepartmentController
{
    public function __construct(
        private SpotRepository $spots,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route(
        '/spots/departement/{department}',
        name: 'spot_department',
        requirements: ['department' => '\w{2,3}'],
        methods: [Request::METHOD_GET],
    )]
    #[Route(
        '/spots/departement/{department}.amp',
        name: 'spot_department_amp',
        requirements: ['department' => '\w{2,3}'],
        defaults: ['amp' => true],
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(string $department, bool $amp = false): Response
    {
        $spots = $this->spots->findByDepartmentCode($department);
        if ([] === $spots) {
            throw new NotFoundHttpException();
        }

        $template = $amp ? 'spot_department/index.amp.html.twig' : 'spot_department/index.html.twig';

        return new Response($this->twig->render($template, [
            'department' => $department,
            'spots' => $spots,
            'amphtml' => $amp ? null : $this->urlGenerator->generate('spot_department_amp', ['department' => $department], UrlGeneratorInterface::ABSOLUTE_URL),
        ]));
    }
}
