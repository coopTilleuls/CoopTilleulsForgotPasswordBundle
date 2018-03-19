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

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\EventListener\RequestEventListener;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class RequestEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestEventListener
     */
    private $listener;
    private $managerMock;
    private $eventMock;
    private $requestMock;
    private $parameterBagMock;

    protected function setUp()
    {
        $this->managerMock = $this->prophesize(PasswordTokenManager::class);
        $this->eventMock = $this->prophesize(GetResponseEvent::class);
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

    public function testDecodeRequestInvalidRoute()
    {
        $this->parameterBagMock->get('_route')->willReturn('foo')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->requestMock->getContent()->shouldNotBeCalled();

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    /**
     * @expectedException \CoopTilleuls\ForgotPasswordBundle\Exception\MissingFieldHttpException
     * @expectedExceptionMessage Parameter "password" is missing.
     */
    public function testDecodeRequestMissingFieldException()
    {
        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->requestMock->getContent()->willReturn(json_encode(['password' => '']))->shouldBeCalledTimes(1);

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    /**
     * @expectedException \CoopTilleuls\ForgotPasswordBundle\Exception\InvalidJsonHttpException
     * @expectedExceptionMessage Invalid JSON data.
     */
    public function testDecodeRequestNoParametersException()
    {
        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->requestMock->getContent()->willReturn()->shouldBeCalledTimes(1);

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    /**
     * @expectedException \CoopTilleuls\ForgotPasswordBundle\Exception\UnauthorizedFieldException
     * @expectedExceptionMessage The parameter "name" is not authorized in your configuration.
     */
    public function testDecodeRequestUnauthorizedException()
    {
        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.reset')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->requestMock->getContent()->willReturn(json_encode(['name' => 'foo']))->shouldBeCalledTimes(1);

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    public function testDecodeRequest()
    {
        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->requestMock->getContent()->willReturn(json_encode(['password' => 'bar']))->shouldBeCalledTimes(1);
        $this->parameterBagMock->set('password', 'bar')->shouldBeCalledTimes(1);

        $this->listener->decodeRequest($this->eventMock->reveal());
    }

    public function testGetTokenFromRequestInvalidRoute()
    {
        $this->parameterBagMock->get('_route')->willReturn('foo')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->requestMock->getContent()->shouldNotBeCalled();

        $this->listener->getTokenFromRequest($this->eventMock->reveal());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testGetTokenFromRequestNoTokenException()
    {
        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->parameterBagMock->get('tokenValue')->willReturn('foo')->shouldBeCalledTimes(1);
        $this->managerMock->findOneByToken('foo')->shouldBeCalledTimes(1);

        $this->listener->getTokenFromRequest($this->eventMock->reveal());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testGetTokenFromRequestInvalidTokenException()
    {
        $tokenMock = $this->prophesize(AbstractPasswordToken::class);

        $this->parameterBagMock->get('_route')->willReturn('coop_tilleuls_forgot_password.update')->shouldBeCalledTimes(1);
        $this->eventMock->isMasterRequest()->willReturn(true)->shouldBeCalledTimes(1);
        $this->parameterBagMock->get('tokenValue')->willReturn('foo')->shouldBeCalledTimes(1);
        $this->managerMock->findOneByToken('foo')->willReturn($tokenMock->reveal())->shouldBeCalledTimes(1);
        $tokenMock->isExpired()->willReturn(true)->shouldBeCalledTimes(1);

        $this->listener->getTokenFromRequest($this->eventMock->reveal());
    }

    public function testGetTokenFromRequest()
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
