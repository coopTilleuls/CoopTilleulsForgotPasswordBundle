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

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\EventListener\RequestEventListener;
use CoopTilleuls\ForgotPasswordBundle\Exception\InvalidJsonHttpException;
use CoopTilleuls\ForgotPasswordBundle\Exception\MissingFieldHttpException;
use CoopTilleuls\ForgotPasswordBundle\Exception\NoParameterException;
use CoopTilleuls\ForgotPasswordBundle\Exception\UnauthorizedFieldException;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderChainInterface;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class RequestEventListenerTest extends TestCase
{
        /**
     * @var RequestEventListener
     */
    private $listener;
    private $passwordTokenManagerMock;
    private $eventMock;
    private $requestMock;
    private $parameterBagMock;
    private $headerBagMock;
    private $inputBagMock;
    private $providerChainMock;
    private $providerMock;

    protected function setUp(): void
    {
        $this->providerMock = $this->createMock(ProviderInterface::class);
        $this->passwordTokenManagerMock = $this->createMock(PasswordTokenManager::class);
        $this->eventMock = $this->createMock(KernelEvent::class);
        $this->requestMock = $this->createMock(Request::class);
        $this->parameterBagMock = $this->createMock(ParameterBag::class);
        $this->headerBagMock = $this->createMock(HeaderBag::class);
        $this->providerChainMock = $this->createMock(ProviderChainInterface::class);
        $this->inputBagMock = $this->createMock(InputBag::class);

        $this->eventMock->expects($this->once())->method('getRequest')->willReturn($this->requestMock);
        $this->requestMock->attributes = $this->parameterBagMock;
        $this->requestMock->query = $this->inputBagMock;
        $this->requestMock->headers = $this->headerBagMock;

        $this->listener = new RequestEventListener(
            $this->passwordTokenManagerMock,
            $this->providerChainMock
        );
    }

    public function testDecodeRequestInvalidRoute(): void
    {
        $this->parameterBagMock->expects($this->once())->method('get')->with('_route')->willReturn('foo');
        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->expects($this->once())->method('isMainRequest')->willReturn(true);
        } else {
            $this->eventMock->expects($this->once())->method('isMasterRequest')->willReturn(true);
        }
        $this->requestMock->expects($this->never())->method('getContent');

        $this->listener->decodeRequest($this->eventMock);
    }

    public function testDecodeRequestMissingFieldException(): void
    {
        $this->expectException(MissingFieldHttpException::class);
        $this->expectExceptionMessage('Parameter "password" is missing.');

        $this->parameterBagMock->expects($this->once())->method('get')->with('_route')->willReturn('coop_tilleuls_forgot_password.update');
        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->expects($this->once())->method('isMainRequest')->willReturn(true);
        } else {
            $this->eventMock->expects($this->once())->method('isMasterRequest')->willReturn(true);
        }
        $this->requestMock->expects($this->once())->method('getContent')->willReturn(json_encode(['password' => '']));

        $this->listener->decodeRequest($this->eventMock);
    }

    public function testDecodeRequestNoParametersException(): void
    {
        $this->expectException(NoParameterException::class);
        $this->expectExceptionMessage('No parameter sent.');

        $this->parameterBagMock->expects($this->once())->method('get')->with('_route')->willReturn('coop_tilleuls_forgot_password.update');
        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->expects($this->once())->method('isMainRequest')->willReturn(true);
        } else {
            $this->eventMock->expects($this->once())->method('isMasterRequest')->willReturn(true);
        }
        $this->requestMock->expects($this->once())->method('getContent')->willReturn('{}');

        $this->listener->decodeRequest($this->eventMock);
    }

    public function testDecodeRequestInvalidJsonHttpException(): void
    {
        $this->expectException(InvalidJsonHttpException::class);
        $this->expectExceptionMessage('Invalid JSON data.');

        $this->parameterBagMock->expects($this->once())->method('get')->with('_route')->willReturn('coop_tilleuls_forgot_password.update');
        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->expects($this->once())->method('isMainRequest')->willReturn(true);
        } else {
            $this->eventMock->expects($this->once())->method('isMasterRequest')->willReturn(true);
        }

        $this->requestMock->expects($this->once())->method('getContent')->willReturn('{');

        $this->listener->decodeRequest($this->eventMock);
    }

    public function testDecodeRequestUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedFieldException::class);
        $this->expectExceptionMessage('The parameter "name" is not authorized in your configuration.');

        $this->headerBagMock->expects($this->once())->method('get')->with('FP-provider')->willReturn('user');
        $this->providerChainMock->expects($this->once())->method('get')->with('user')->willReturn($this->providerMock);
        $this->providerMock->expects($this->once())->method('getUserAuthorizedFields')->willReturn(['username', 'email']);
        $this->parameterBagMock->expects($this->once())->method('get')->with('_route')->willReturn('coop_tilleuls_forgot_password.reset');
        $this->parameterBagMock->expects($this->once())->method('set')->with('provider', $this->providerMock);

        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->expects($this->once())->method('isMainRequest')->willReturn(true);
        } else {
            $this->eventMock->expects($this->once())->method('isMasterRequest')->willReturn(true);
        }
        $this->requestMock->expects($this->once())->method('getContent')->willReturn(json_encode(['name' => 'foo']));

        $this->listener->decodeRequest($this->eventMock);
    }

    public function testDecodeRequest(): void
    {
        $this->headerBagMock->expects($this->once())->method('get')->with('FP-provider')->willReturn('user');
        $this->providerChainMock->expects($this->once())->method('get')->with('user')->willReturn($this->providerMock);
        $this->providerMock->expects($this->never())->method('getUserAuthorizedFields');
        $this->providerMock->expects($this->once())->method('getUserPasswordField')->willReturn('password');
        $this->parameterBagMock->expects($this->once())->method('get')->with('_route')->willReturn('coop_tilleuls_forgot_password.update');
        $this->parameterBagMock->expects($this->exactly(2))->method('set')->withConsecutive(['provider', $this->providerMock], ['password', 'bar']);

        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->expects($this->once())->method('isMainRequest')->willReturn(true);
        } else {
            $this->eventMock->expects($this->once())->method('isMasterRequest')->willReturn(true);
        }
        $this->requestMock->expects($this->once())->method('getContent')->willReturn(json_encode(['password' => 'bar']));

        $this->listener->decodeRequest($this->eventMock);
    }

    public function testGetTokenFromRequestInvalidRoute(): void
    {
        $this->parameterBagMock->expects($this->once())->method('get')->with('_route')->willReturn('foo');
        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->expects($this->once())->method('isMainRequest')->willReturn(true);
        } else {
            $this->eventMock->expects($this->once())->method('isMasterRequest')->willReturn(true);
        }
        $this->requestMock->expects($this->never())->method('getContent');

        $this->listener->getTokenFromRequest($this->eventMock);
    }

    public function testGetTokenFromRequestNoTokenException(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->headerBagMock->expects($this->once())->method('get')->with('FP-provider')->willReturn('admin');
        $this->providerChainMock->expects($this->once())->method('get')->with('admin')->willReturn($this->providerMock);
        $this->parameterBagMock->expects($this->exactly(2))->method('get')->withConsecutive(['_route'], ['tokenValue'])->willReturnOnConsecutiveCalls('coop_tilleuls_forgot_password.update', 'foo');

        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->expects($this->once())->method('isMainRequest')->willReturn(true);
        } else {
            $this->eventMock->expects($this->once())->method('isMasterRequest')->willReturn(true);
        }
        $this->passwordTokenManagerMock->expects($this->once())->method('findOneByToken')->with('foo', $this->providerMock);

        $this->listener->getTokenFromRequest($this->eventMock);
    }

    public function testGetTokenFromRequestInvalidTokenException(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $tokenMock = $this->createMock(AbstractPasswordToken::class);

        $this->headerBagMock->expects($this->once())->method('get')->with('FP-provider')->willReturn('admin');
        $this->providerChainMock->expects($this->once())->method('get')->with('admin')->willReturn($this->providerMock);
        $this->parameterBagMock->expects($this->exactly(2))->method('get')->withConsecutive(['_route'], ['tokenValue'])->willReturnOnConsecutiveCalls('coop_tilleuls_forgot_password.update', 'foo');

        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->expects($this->once())->method('isMainRequest')->willReturn(true);
        } else {
            $this->eventMock->expects($this->once())->method('isMasterRequest')->willReturn(true);
        }
        $this->passwordTokenManagerMock->expects($this->once())->method('findOneByToken')->with('foo', $this->providerMock)->willReturn($tokenMock);
        $tokenMock->expects($this->once())->method('isExpired')->willReturn(true);

        $this->listener->getTokenFromRequest($this->eventMock);
    }

    public function testGetTokenFromRequest(): void
    {
        $tokenMock = $this->createMock(AbstractPasswordToken::class);
        $this->headerBagMock->expects($this->once())->method('get')->with('FP-provider')->willReturn('user');
        $this->providerChainMock->expects($this->once())->method('get')->with('user')->willReturn($this->providerMock);
        $this->parameterBagMock->expects($this->exactly(2))->method('get')->withConsecutive(['_route'], ['tokenValue'])->willReturnOnConsecutiveCalls('coop_tilleuls_forgot_password.update', 'foo');
        $this->parameterBagMock->expects($this->exactly(2))->method('set')->withConsecutive(['token', $tokenMock], ['provider', $this->providerMock]);

        if (method_exists(KernelEvent::class, 'isMainRequest')) {
            $this->eventMock->expects($this->once())->method('isMainRequest')->willReturn(true);
        } else {
            $this->eventMock->expects($this->once())->method('isMasterRequest')->willReturn(true);
        }
        $this->passwordTokenManagerMock->expects($this->once())->method('findOneByToken')->with('foo', $this->providerMock)->willReturn($tokenMock);
        $tokenMock->expects($this->once())->method('isExpired')->willReturn(false);

        $this->listener->getTokenFromRequest($this->eventMock);
    }
}
