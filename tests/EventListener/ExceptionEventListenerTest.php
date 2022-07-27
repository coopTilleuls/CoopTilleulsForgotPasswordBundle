<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\ForgotPasswordBundle\EventListener;

use CoopTilleuls\ForgotPasswordBundle\EventListener\ExceptionEventListener;
use CoopTilleuls\ForgotPasswordBundle\Exception\MissingFieldHttpException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Tests\ForgotPasswordBundle\ProphecyTrait;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class ExceptionEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testOnKernelExceptionInvalid(): void
    {
        if (class_exists(ExceptionEvent::class)) {
            $eventMock = $this->prophesize(ExceptionEvent::class);
            $eventMock->getThrowable()->willReturn($this->prophesize(\Exception::class)->reveal())->shouldBeCalledOnce();
        } else {
            $eventMock = $this->prophesize(GetResponseForExceptionEvent::class);
            $eventMock->getException()->willReturn($this->prophesize(\Exception::class)->reveal())->shouldBeCalledOnce();
        }

        if (method_exists(ExceptionEvent::class, 'isMainRequest')) {
            $eventMock->isMainRequest()->willReturn(true)->shouldBeCalledOnce();
        } else {
            $eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledOnce();
        }
        $eventMock->setResponse(Argument::any())->shouldNotBeCalled();

        $listener = new ExceptionEventListener();
        $listener->onKernelException($eventMock->reveal());
    }

    public function testOnKernelExceptionSubRequest(): void
    {
        if (class_exists(ExceptionEvent::class)) {
            $eventMock = $this->prophesize(ExceptionEvent::class);
            $eventMock->getThrowable()->willReturn($this->prophesize(\Exception::class)->reveal())->shouldBeCalledOnce();
        } else {
            $eventMock = $this->prophesize(GetResponseForExceptionEvent::class);
            $eventMock->getException()->willReturn($this->prophesize(\Exception::class)->reveal())->shouldBeCalledOnce();
        }

        if (method_exists(ExceptionEvent::class, 'isMainRequest')) {
            $eventMock->isMainRequest()->willReturn(false)->shouldBeCalledOnce();
        } else {
            $eventMock->isMasterRequest()->willReturn(false)->shouldBeCalledOnce();
        }
        $eventMock->setResponse(Argument::any())->shouldNotBeCalled();

        $listener = new ExceptionEventListener();
        $listener->onKernelException($eventMock->reveal());
    }

    public function testOnKernelException(): void
    {
        // Cannot mock exception as it should implement JsonHttpExceptionInterface
        // and extends \Exception, but method \Exception::getMessage is final
        $exception = new MissingFieldHttpException('foo');

        if (class_exists(ExceptionEvent::class)) {
            $eventMock = $this->prophesize(ExceptionEvent::class);
            $eventMock->getThrowable()->willReturn($exception)->shouldBeCalledOnce();
        } else {
            $eventMock = $this->prophesize(GetResponseForExceptionEvent::class);
            $eventMock->getException()->willReturn($exception)->shouldBeCalledOnce();
        }
        if (method_exists(ExceptionEvent::class, 'isMainRequest')) {
            $eventMock->isMainRequest()->willReturn(true)->shouldBeCalledOnce();
        } else {
            $eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledOnce();
        }
        $eventMock->setResponse(
            Argument::that(
                function ($response) {
                    return $response instanceof JsonResponse &&
                    json_encode(
                        ['message' => 'Parameter "foo" is missing.'],
                        15
                    ) === $response->getContent() &&
                    400 === $response->getStatusCode();
                }
            )
        )->shouldBeCalledOnce();

        $listener = new ExceptionEventListener();
        $listener->onKernelException($eventMock->reveal());
    }
}
