<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent CHALAMON <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace CoopTilleuls\ForgotPasswordBundle\Tests\EventListener;

use CoopTilleuls\ForgotPasswordBundle\EventListener\ExceptionEventListener;
use CoopTilleuls\ForgotPasswordBundle\Exception\MissingFieldHttpException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class ExceptionEventListenerTest extends TestCase
{
        public function testOnKernelExceptionInvalid(): void
    {
        if (class_exists(ExceptionEvent::class)) {
            $eventMock = $this->createMock(ExceptionEvent::class);
            $eventMock->expects($this->once())->method('getThrowable')->willReturn($this->createMock(\Exception::class));
        } else {
            $eventMock = $this->createMock(GetResponseForExceptionEvent::class);
            $eventMock->expects($this->once())->method('getException')->willReturn($this->createMock(\Exception::class));
        }

        if (method_exists(ExceptionEvent::class, 'isMainRequest')) {
            $eventMock->expects($this->once())->method('isMainRequest')->willReturn(true);
        } else {
            $eventMock->expects($this->once())->method('isMasterRequest')->willReturn(true);
        }
        $eventMock->expects($this->never())->method('setResponse');

        $listener = new ExceptionEventListener();
        $listener->onKernelException($eventMock);
    }

    public function testOnKernelExceptionSubRequest(): void
    {
        if (class_exists(ExceptionEvent::class)) {
            $eventMock = $this->createMock(ExceptionEvent::class);
            $eventMock->expects($this->once())->method('getThrowable')->willReturn($this->createMock(\Exception::class));
        } else {
            $eventMock = $this->createMock(GetResponseForExceptionEvent::class);
            $eventMock->expects($this->once())->method('getException')->willReturn($this->createMock(\Exception::class));
        }

        if (method_exists(ExceptionEvent::class, 'isMainRequest')) {
            $eventMock->expects($this->once())->method('isMainRequest')->willReturn(false);
        } else {
            $eventMock->expects($this->once())->method('isMasterRequest')->willReturn(false);
        }
        $eventMock->expects($this->never())->method('setResponse');

        $listener = new ExceptionEventListener();
        $listener->onKernelException($eventMock);
    }

    public function testOnKernelException(): void
    {
        // Cannot mock exception as it should implement JsonHttpExceptionInterface
        // and extends \Exception, but method \Exception::getMessage is final
        $exception = new MissingFieldHttpException('foo');

        if (class_exists(ExceptionEvent::class)) {
            $eventMock = $this->createMock(ExceptionEvent::class);
            $eventMock->expects($this->once())->method('getThrowable')->willReturn($exception);
        } else {
            $eventMock = $this->createMock(GetResponseForExceptionEvent::class);
            $eventMock->expects($this->once())->method('getException')->willReturn($exception);
        }
        if (method_exists(ExceptionEvent::class, 'isMainRequest')) {
            $eventMock->expects($this->once())->method('isMainRequest')->willReturn(true);
        } else {
            $eventMock->expects($this->once())->method('isMasterRequest')->willReturn(true);
        }
        $eventMock->expects($this->once())->method('setResponse')->with($this->callback(function ($response) {
            return $response instanceof JsonResponse
                && json_encode(
                    ['message' => 'Parameter "foo" is missing.'],
                    15
                ) === $response->getContent()
                && 400 === $response->getStatusCode();
        }));

        $listener = new ExceptionEventListener();
        $listener->onKernelException($eventMock);
    }
}
