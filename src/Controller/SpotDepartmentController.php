<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SpotRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final readonly class SpotDepartmentController
{
    public function __construct(
        private SpotRepository $spots,
        private Environment $twig,
    ) {
    }

    #[Route(
        '/spots/departement/{department}',
        name: 'spot_department',
        requirements: ['department' => '\w{2,3}'],
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(string $department): Response
    {
        $spots = $this->spots->findByDepartmentCode($department);
        if ([] === $spots) {
            throw new NotFoundHttpException();
        }

        return new Response($this->twig->render('spot_department/index.html.twig', [
            'department' => $department,
            'spots' => $spots,
        ]));
    }
}
