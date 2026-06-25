<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SpotRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final readonly class SpotDepartmentsController
{
    public function __construct(
        private SpotRepository $spots,
        private Environment $twig,
    ) {
    }

    #[Route(
        '/spots/departements',
        name: 'spot_departments',
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(): Response
    {
        $departments = $this->spots->findAllDepartmentCodesWithCount();

        return new Response($this->twig->render('spot_departments/index.html.twig', [
            'departments' => $departments,
        ]));
    }
}
