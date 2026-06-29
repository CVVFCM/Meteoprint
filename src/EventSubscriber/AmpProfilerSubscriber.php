<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Profiler\Profiler;

/**
 * The Web Debug Toolbar injects HTML + scripts before </body>, which is invalid AMP (and
 * would otherwise be cached into the public AMP responses). Disable the profiler for AMP
 * routes so their output stays valid. Only registered where the profiler exists (dev/test).
 */
#[When('dev')]
#[When('test')]
final readonly class AmpProfilerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'profiler')]
        private Profiler $profiler,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onController'];
    }

    public function onController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $route = $event->getRequest()->attributes->getString('_route');
        if (str_ends_with($route, '_amp')) {
            $this->profiler->disable();
        }
    }
}
