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

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\EventListener\RequestEventListener;
use CoopTilleuls\ForgotPasswordBundle\Exception\InvalidJsonHttpException;
use CoopTilleuls\ForgotPasswordBundle\Exception\MissingFieldHttpException;
use CoopTilleuls\ForgotPasswordBundle\Exception\NoParameterException;
use CoopTilleuls\ForgotPasswordBundle\Exception\UnauthorizedFieldException;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\ForgotPasswordBundle\ProphecyTrait;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class RequestEventListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var RequestEventListener
     */
    private $listener;
    private $managerMock;
    private $eventMock;
    private $requestMock;
    private $parameterBagMock;

    protected function setUp(): void
    {
        $this->managerMock = $this->prophesize(PasswordTokenManager::class);
        $this->eventMock = $this->prophesize(KernelEvent::class);
        $this->requestMock = $this->prophesize(Request::class);
        $this->parameterBagMock = $this->prophesize(ParameterBag::class);

        $this->eventMock->getRequest()->willReturn($this->requestMock->reveal())->shouldBeCalledOnce();
        $this->requestMock->attributes = $this->parameterBagMock->reveal();

        $this->listener = new RequestEventListener(
            ['email', 'username'],
            'password',
            $this->managerMock->reveal()
        );
    }

    public function testDecodeRequestInvalidRoute(): void
    {
        $this->parameterBagMock->get('_route')->willReturn('foo')->shouldBeCalledOnce();
        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->isMainRequest()->willReturn(true)->shouldBeCalledOnce();
        } else {
            $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledOnce();
        }
        $this->requestMock->getContent()->shouldNotBeCalled();

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    public function testDecodeRequestMissingFieldException(): void
    {
        $this->expectException(MissingFieldHttpException::class);
        $this->expectExceptionMessage('Parameter "password" is missing.');

        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledOnce();
        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->isMainRequest()->willReturn(true)->shouldBeCalledOnce();
        } else {
            $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledOnce();
        }
        $this->requestMock->getContent()->willReturn(json_encode(['password' => '']))->shouldBeCalledOnce();

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    public function testDecodeRequestNoParametersException(): void
    {
        $this->expectException(NoParameterException::class);
        $this->expectExceptionMessage('No parameter sent.');

        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledOnce();
        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->isMainRequest()->willReturn(true)->shouldBeCalledOnce();
        } else {
            $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledOnce();
        }
        $this->requestMock->getContent()->willReturn('{}')->shouldBeCalledOnce();

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    public function testDecodeRequestInvalidJsonHttpException(): void
    {
        $this->expectException(InvalidJsonHttpException::class);
        $this->expectExceptionMessage('Invalid JSON data.');

        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledOnce();
        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->isMainRequest()->willReturn(true)->shouldBeCalledOnce();
        } else {
            $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledOnce();
        }

        $this->requestMock->getContent()->willReturn('{')->shouldBeCalledOnce();

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    public function testDecodeRequestUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedFieldException::class);
        $this->expectExceptionMessage('The parameter "name" is not authorized in your configuration.');

        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.reset')->shouldBeCalledOnce();
        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->isMainRequest()->willReturn(true)->shouldBeCalledOnce();
        } else {
            $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledOnce();
        }
        $this->requestMock->getContent()->willReturn(json_encode(['name' => 'foo']))->shouldBeCalledOnce();

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    public function testDecodeRequest(): void
    {
        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledOnce();
        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->isMainRequest()->willReturn(true)->shouldBeCalledOnce();
        } else {
            $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledOnce();
        }
        $this->requestMock->getContent()->willReturn(json_encode(['password' => 'bar']))->shouldBeCalledOnce();
        $this->parameterBagMock->set('password', 'bar')->shouldBeCalledOnce();

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    public function testGetTokenFromRequestInvalidRoute(): void
    {
        $this->parameterBagMock->get('_route')->willReturn('foo')->shouldBeCalledOnce();
        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->isMainRequest()->willReturn(true)->shouldBeCalledOnce();
        } else {
            $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledOnce();
        }
        $this->requestMock->getContent()->shouldNotBeCalled();

        $this->listener->getTokenFromRequest($this->eventMock->reveal());
    }

    public function testGetTokenFromRequestNoTokenException(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledOnce();
        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->isMainRequest()->willReturn(true)->shouldBeCalledOnce();
        } else {
            $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledOnce();
        }
        $this->parameterBagMock->get('token')->willReturn('foo')->shouldBeCalledOnce();
        $this->managerMock->findOneByToken('foo')->shouldBeCalledOnce();

        $this->listener->getTokenFromRequest($this->eventMock->reveal());
    }

    public function testGetTokenFromRequestInvalidTokenException(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $tokenMock = $this->prophesize(AbstractPasswordToken::class);

        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledOnce();
        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->isMainRequest()->willReturn(true)->shouldBeCalledOnce();
        } else {
            $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledOnce();
        }
        $this->parameterBagMock->get('token')->willReturn('foo')->shouldBeCalledOnce();
        $this->managerMock->findOneByToken('foo')->willReturn($tokenMock->reveal())->shouldBeCalledOnce();
        $tokenMock->isExpired()->willReturn(true)->shouldBeCalledOnce();

        $this->listener->getTokenFromRequest($this->eventMock->reveal());
    }

    public function testGetTokenFromRequest(): void
    {
        $tokenMock = $this->prophesize(AbstractPasswordToken::class);

        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledOnce();
        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->isMainRequest()->willReturn(true)->shouldBeCalledOnce();
        } else {
            $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledOnce();
        }
        $this->parameterBagMock->get('token')->willReturn('foo')->shouldBeCalledOnce();
        $this->managerMock->findOneByToken('foo')->willReturn($tokenMock->reveal())->shouldBeCalledOnce();
        $tokenMock->isExpired()->willReturn(false)->shouldBeCalledOnce();
        $this->parameterBagMock->set('token', $tokenMock->reveal())->shouldBeCalledOnce();

        $this->listener->getTokenFromRequest($this->eventMock->reveal());
    }
}
