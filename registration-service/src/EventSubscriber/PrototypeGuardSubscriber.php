<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Keeps the prototype a prototype.
 *
 * Every page is refused unless REGISTRY_MODE is "synthetic". There is no
 * other mode in the code: a live registration service needs Ministry
 * approval and a separate review. Responses also carry strict security
 * headers, are never cached and ask search engines not to index them.
 */
final class PrototypeGuardSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%env(REGISTRY_MODE)%')]
        private readonly string $mode,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 512],
            KernelEvents::RESPONSE => ['onResponse', -512],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest() && $this->mode !== 'synthetic') {
            $event->setResponse(new Response('The registration prototype is disabled.', Response::HTTP_SERVICE_UNAVAILABLE, ['Content-Type' => 'text/plain']));
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $headers = $event->getResponse()->headers;
        $headers->set('Content-Security-Policy', "default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'no-referrer');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('X-Robots-Tag', 'noindex, nofollow');
        $headers->set('Cache-Control', 'no-store, private');
    }
}
