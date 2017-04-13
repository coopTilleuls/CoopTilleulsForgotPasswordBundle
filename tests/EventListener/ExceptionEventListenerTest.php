<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ForgotPasswordBundle\EventListener;

use CoopTilleuls\ForgotPasswordBundle\EventListener\ExceptionEventListener;
use CoopTilleuls\ForgotPasswordBundle\Exception\UserNotFoundHttpException;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class ExceptionEventListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnKernelExceptionInvalid()
    {
        $eventMock = $this->prophesize(GetResponseForExceptionEvent::class);

        $eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $eventMock->getException()->willReturn($this->prophesize(\Exception::class)->reveal())->shouldBeCalledTimes(1);
        $eventMock->setResponse(Argument::any())->shouldNotBeCalled();

        $listener = new ExceptionEventListener();
        $listener->onKernelException($eventMock->reveal());
    }

    public function testOnKernelExceptionSubRequest()
    {
        $eventMock = $this->prophesize(GetResponseForExceptionEvent::class);

        $eventMock->isMasterRequest()->willReturn(false)->shouldBeCalledTimes(1);
        $eventMock->getException()->willReturn($this->prophesize(\Exception::class)->reveal())->shouldBeCalledTimes(1);
        $eventMock->setResponse(Argument::any())->shouldNotBeCalled();

        $listener = new ExceptionEventListener();
        $listener->onKernelException($eventMock->reveal());
    }

    public function testOnKernelException()
    {
        $eventMock = $this->prophesize(GetResponseForExceptionEvent::class);
        // Cannot mock exception as it should implement JsonHttpExceptionInterface
        // and extends \Exception, but method \Exception::getMessage is final
        $exception = new UserNotFoundHttpException('foo', 'bar');

        $eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $eventMock->getException()->willReturn($exception)->shouldBeCalledTimes(1);
        $eventMock->setResponse(
            Argument::that(
                function ($response) {
                    return $response instanceof JsonResponse &&
                    json_encode(
                        ['message' => 'User with field "foo" equal to "bar" cannot be found.'],
                        15
                    ) === $response->getContent() &&
                    400 === $response->getStatusCode();
                }
            )
        )->shouldBeCalledTimes(1);

        $listener = new ExceptionEventListener();
        $listener->onKernelException($eventMock->reveal());
    }
}
