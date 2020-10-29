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

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class RequestEventListenerTest extends TestCase
{
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

        $this->eventMock->getRequest()->willReturn($this->requestMock->reveal())->shouldBeCalledTimes(1);
        $this->requestMock->attributes = $this->parameterBagMock->reveal();

        $this->listener = new RequestEventListener(
            ['email', 'username'],
            'password',
            $this->managerMock->reveal()
        );
    }

    public function testDecodeRequestInvalidRoute(): void
    {
        $this->parameterBagMock->get('_route')->willReturn('foo')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->requestMock->getContent()->shouldNotBeCalled();

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    public function testDecodeRequestMissingFieldException(): void
    {
        $this->expectException(MissingFieldHttpException::class);
        $this->expectExceptionMessage('Parameter "password" is missing.');

        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->requestMock->getContent()->willReturn(json_encode(['password' => '']))->shouldBeCalledTimes(1);

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    public function testDecodeRequestNoParametersException(): void
    {
        $this->expectException(NoParameterException::class);
        $this->expectExceptionMessage('No parameter sent.');

        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->requestMock->getContent()->willReturn('{}')->shouldBeCalledTimes(1);

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    public function testDecodeRequestInvalidJsonHttpException(): void
    {
        $this->expectException(InvalidJsonHttpException::class);
        $this->expectExceptionMessage('Invalid JSON data.');

        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->requestMock->getContent()->willReturn('{')->shouldBeCalledTimes(1);

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    public function testDecodeRequestUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedFieldException::class);
        $this->expectExceptionMessage('The parameter "name" is not authorized in your configuration.');

        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.reset')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->requestMock->getContent()->willReturn(json_encode(['name' => 'foo']))->shouldBeCalledTimes(1);

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    public function testDecodeRequest(): void
    {
        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->requestMock->getContent()->willReturn(json_encode(['password' => 'bar']))->shouldBeCalledTimes(1);
        $this->parameterBagMock->set('password', 'bar')->shouldBeCalledTimes(1);

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    public function testGetTokenFromRequestInvalidRoute(): void
    {
        $this->parameterBagMock->get('_route')->willReturn('foo')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->requestMock->getContent()->shouldNotBeCalled();

        $this->listener->getTokenFromRequest($this->eventMock->reveal());
    }

    public function testGetTokenFromRequestNoTokenException(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->parameterBagMock->get('tokenValue')->willReturn('foo')->shouldBeCalledTimes(1);
        $this->managerMock->findOneByToken('foo')->shouldBeCalledTimes(1);

        $this->listener->getTokenFromRequest($this->eventMock->reveal());
    }

    public function testGetTokenFromRequestInvalidTokenException(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $tokenMock = $this->prophesize(AbstractPasswordToken::class);

        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->parameterBagMock->get('tokenValue')->willReturn('foo')->shouldBeCalledTimes(1);
        $this->managerMock->findOneByToken('foo')->willReturn($tokenMock->reveal())->shouldBeCalledTimes(1);
        $tokenMock->isExpired()->willReturn(true)->shouldBeCalledTimes(1);

        $this->listener->getTokenFromRequest($this->eventMock->reveal());
    }

    public function testGetTokenFromRequest(): void
    {
        $tokenMock = $this->prophesize(AbstractPasswordToken::class);

        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->parameterBagMock->get('tokenValue')->willReturn('foo')->shouldBeCalledTimes(1);
        $this->managerMock->findOneByToken('foo')->willReturn($tokenMock->reveal())->shouldBeCalledTimes(1);
        $tokenMock->isExpired()->willReturn(false)->shouldBeCalledTimes(1);
        $this->parameterBagMock->set('token', $tokenMock->reveal())->shouldBeCalledTimes(1);

        $this->listener->getTokenFromRequest($this->eventMock->reveal());
    }
}
