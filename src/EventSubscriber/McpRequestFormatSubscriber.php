<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Forces the serialization format of MCP tool results to plain JSON. API Platform's
 * StructuredContentProcessor derives the format from the request ("jsonld" when unset,
 * which litters tool payloads with @context/@id/@var); the api_platform.mcp.format
 * option only rewrites operation metadata, not the request format, in the current
 * (experimental) integration.
 */
final readonly class McpRequestFormatSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onRequest'];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ('_mcp_endpoint' === $request->attributes->getString('_route')) {
            $request->setRequestFormat('json');
        }
    }
}
