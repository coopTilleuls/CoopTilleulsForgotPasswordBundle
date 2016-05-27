<?php

namespace ForgotPasswordBundle\Tests\Request\ParamConverter;

use Doctrine\ORM\EntityRepository;
use ForgotPasswordBundle\Entity\AbstractPasswordToken;
use ForgotPasswordBundle\Request\ParamConverter\PasswordTokenParamConverter;
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
        $repositoryMock = $this->prophesize(EntityRepository::class);
        $requestMock = $this->prophesize(Request::class);
        $paramConverterMock = $this->prophesize(ParamConverter::class);

        $paramConverterMock->getName()->willReturn('token')->shouldBeCalledTimes(1);
        $requestMock->get('token')->willReturn('12345')->shouldBeCalledTimes(1);
        $paramConverterMock->isOptional()->willReturn(false)->shouldNotBeCalled();

        $repositoryMock->findOneBy(['token' => '12345'])->shouldBeCalledTimes(1);

        $converter = new PasswordTokenParamConverter($repositoryMock->reveal());
        $converter->apply($requestMock->reveal(), $paramConverterMock->reveal());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage The token has expired.
     */
    public function testApplyExpiredTokenException()
    {
        $repositoryMock = $this->prophesize(EntityRepository::class);
        $requestMock = $this->prophesize(Request::class);
        $paramConverterMock = $this->prophesize(ParamConverter::class);
        $tokenMock = $this->prophesize(AbstractPasswordToken::class);

        $paramConverterMock->getName()->willReturn('token')->shouldBeCalledTimes(1);
        $requestMock->get('token')->willReturn('12345')->shouldBeCalledTimes(1);
        $paramConverterMock->isOptional()->willReturn(false)->shouldNotBeCalled();

        $repositoryMock->findOneBy(['token' => '12345'])->willReturn($tokenMock->reveal())->shouldBeCalledTimes(1);
        $tokenMock->getExpiresAt()->willReturn(new \DateTime('-1 minute'))->shouldBeCalledTimes(1);

        $converter = new PasswordTokenParamConverter($repositoryMock->reveal());
        $converter->apply($requestMock->reveal(), $paramConverterMock->reveal());
    }
}
