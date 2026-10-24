<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\EventSubscriber\PrototypeGuardSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class PrototypeGuardTest extends TestCase
{
    private function handle(string $mode): ?int
    {
        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), Request::create('/register'), HttpKernelInterface::MAIN_REQUEST);
        (new PrototypeGuardSubscriber($mode))->onRequest($event);

        return $event->getResponse()?->getStatusCode();
    }

    public function testOnlySyntheticModeServesPages(): void
    {
        self::assertNull($this->handle('synthetic'));
        self::assertSame(503, $this->handle('live'));
        self::assertSame(503, $this->handle(''));
    }
}
