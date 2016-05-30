<?php

namespace ForgotPasswordBundle\Request\ParamConverter;

use ForgotPasswordBundle\Entity\AbstractPasswordToken;
use ForgotPasswordBundle\Manager\PasswordTokenManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;

class PasswordTokenParamConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Invalid token.
     */
    public function testApplyInvalidTokenException()
    {
        $passwordTokenManagerMock = $this->prophesize(PasswordTokenManager::class);
        $requestMock = $this->prophesize(Request::class);
        $paramConverterMock = $this->prophesize(ParamConverter::class);

        $paramConverterMock->getName()->willReturn('token')->shouldBeCalledTimes(1);
        $requestMock->get('token')->willReturn('12345')->shouldBeCalledTimes(1);
        $paramConverterMock->isOptional()->willReturn(false)->shouldNotBeCalled();

        $passwordTokenManagerMock->findOneByToken('12345')->shouldBeCalledTimes(1);

        $converter = new PasswordTokenParamConverter($passwordTokenManagerMock->reveal());
        $converter->apply($requestMock->reveal(), $paramConverterMock->reveal());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage The token has expired.
     */
    public function testApplyExpiredTokenException()
    {
        $passwordTokenManagerMock = $this->prophesize(PasswordTokenManager::class);
        $requestMock = $this->prophesize(Request::class);
        $paramConverterMock = $this->prophesize(ParamConverter::class);
        $tokenMock = $this->prophesize(AbstractPasswordToken::class);

        $paramConverterMock->getName()->willReturn('token')->shouldBeCalledTimes(1);
        $requestMock->get('token')->willReturn('12345')->shouldBeCalledTimes(1);
        $paramConverterMock->isOptional()->willReturn(false)->shouldNotBeCalled();

        $passwordTokenManagerMock->findOneByToken('12345')->willReturn($tokenMock->reveal())->shouldBeCalledTimes(1);
        $tokenMock->getExpiresAt()->willReturn(new \DateTime('-1 minute'))->shouldBeCalledTimes(1);

        $converter = new PasswordTokenParamConverter($passwordTokenManagerMock->reveal());
        $converter->apply($requestMock->reveal(), $paramConverterMock->reveal());
    }

    public function testApplyNoRequestParameter()
    {
        $passwordTokenManagerMock = $this->prophesize(PasswordTokenManager::class);
        $requestMock = $this->prophesize(Request::class);
        $paramConverterMock = $this->prophesize(ParamConverter::class);

        $paramConverterMock->getName()->willReturn('token')->shouldBeCalledTimes(1);
        $requestMock->get('token')->shouldBeCalledTimes(1);

        $converter = new PasswordTokenParamConverter($passwordTokenManagerMock->reveal());
        $this->assertFalse($converter->apply($requestMock->reveal(), $paramConverterMock->reveal()));
    }

    public function testSupports()
    {
        $passwordTokenManagerMock = $this->prophesize(PasswordTokenManager::class);
        $paramConverterMock = $this->prophesize(ParamConverter::class);

        $paramConverterMock->getClass()->willReturn(AbstractPasswordToken::class)->shouldBeCalledTimes(1);

        $converter = new PasswordTokenParamConverter($passwordTokenManagerMock->reveal());
        $this->assertTrue($converter->supports($paramConverterMock->reveal()));
    }
}
