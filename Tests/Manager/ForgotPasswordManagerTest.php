<?php

namespace CoopTilleuls\ForgotPasswordBundle\Manager;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Event\ForgotPasswordEvent;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ForgotPasswordManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectProphecy
     */
    private $tokenStorageMock;

    /**
     * @var ObjectProphecy
     */
    private $requestStackMock;

    /**
     * @var ObjectProphecy
     */
    private $passwordTokenManagerMock;

    /**
     * @var ObjectProphecy
     */
    private $dispatcherMock;

    /**
     * @var ObjectProphecy
     */
    private $doctrineMock;

    /**
     * @var ObjectProphecy
     */
    private $entityManagerMock;

    protected function setUp()
    {
        $this->tokenStorageMock = $this->prophesize(TokenStorageInterface::class);
        $this->requestStackMock = $this->prophesize(RequestStack::class);
        $this->passwordTokenManagerMock = $this->prophesize(PasswordTokenManager::class);
        $this->dispatcherMock = $this->prophesize(EventDispatcherInterface::class);
        $this->doctrineMock = $this->prophesize(Registry::class);
        $this->entityManagerMock = $this->prophesize(EntityManagerInterface::class);
        $this->doctrineMock->getManager()->willReturn($this->entityManagerMock->reveal());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testResetPasswordAccessDeniedException()
    {
        $tokenMock = $this->prophesize(TokenInterface::class);
        $userMock = $this->prophesize(UserInterface::class);

        $this->tokenStorageMock->getToken()->willReturn($tokenMock->reveal())->shouldBeCalledTimes(1);
        $tokenMock->getUser()->willReturn($userMock->reveal())->shouldBeCalledTimes(1);

        $manager = new ForgotPasswordManager(
            $this->tokenStorageMock->reveal(),
            $this->requestStackMock->reveal(),
            $this->passwordTokenManagerMock->reveal(),
            $this->dispatcherMock->reveal(),
            $this->doctrineMock->reveal(),
            'AppBundle\Entity\User',
            'email'
        );
        $manager->resetPassword();
    }

    public function testResetPasswordNoRequestParameter()
    {
        $tokenMock = $this->prophesize(TokenInterface::class);
        $requestMock = $this->prophesize(Request::class);

        $this->tokenStorageMock->getToken()->willReturn($tokenMock->reveal())->shouldBeCalledTimes(1);
        $tokenMock->getUser()->shouldBeCalledTimes(1);

        $this->requestStackMock->getCurrentRequest()->willReturn($requestMock->reveal())->shouldBeCalledTimes(1);
        $requestMock->get('email')->shouldBeCalledTimes(1);

        $manager = new ForgotPasswordManager(
            $this->tokenStorageMock->reveal(),
            $this->requestStackMock->reveal(),
            $this->passwordTokenManagerMock->reveal(),
            $this->dispatcherMock->reveal(),
            $this->doctrineMock->reveal(),
            'AppBundle\Entity\User',
            'email'
        );
        $this->assertFalse($manager->resetPassword());
    }

    public function testResetPasswordNoUser()
    {
        $tokenMock = $this->prophesize(TokenInterface::class);
        $requestMock = $this->prophesize(Request::class);
        $repositoryMock = $this->prophesize(EntityRepository::class);

        $this->tokenStorageMock->getToken()->willReturn($tokenMock->reveal())->shouldBeCalledTimes(1);
        $tokenMock->getUser()->shouldBeCalledTimes(1);

        $this->requestStackMock->getCurrentRequest()->willReturn($requestMock->reveal())->shouldBeCalledTimes(1);
        $requestMock->get('email')->willReturn('john.doe@example.com')->shouldBeCalledTimes(1);

        $this->entityManagerMock->getRepository('AppBundle\Entity\User')
            ->willReturn($repositoryMock->reveal())
            ->shouldBeCalledTimes(1);
        $repositoryMock->findOneBy(['email' => 'john.doe@example.com'])->shouldBeCalledTimes(1);

        $manager = new ForgotPasswordManager(
            $this->tokenStorageMock->reveal(),
            $this->requestStackMock->reveal(),
            $this->passwordTokenManagerMock->reveal(),
            $this->dispatcherMock->reveal(),
            $this->doctrineMock->reveal(),
            'AppBundle\Entity\User',
            'email'
        );
        $this->assertFalse($manager->resetPassword());
    }

    public function testResetPassword()
    {
        $tokenMock = $this->prophesize(TokenInterface::class);
        $requestMock = $this->prophesize(Request::class);
        $repositoryMock = $this->prophesize(EntityRepository::class);
        $userMock = $this->prophesize(UserInterface::class);

        $this->tokenStorageMock->getToken()->willReturn($tokenMock->reveal())->shouldBeCalledTimes(1);
        $tokenMock->getUser()->shouldBeCalledTimes(1);

        $this->requestStackMock->getCurrentRequest()->willReturn($requestMock->reveal())->shouldBeCalledTimes(1);
        $requestMock->get('email')->willReturn('john.doe@example.com')->shouldBeCalledTimes(1);

        $this->entityManagerMock->getRepository('AppBundle\Entity\User')
            ->willReturn($repositoryMock->reveal())
            ->shouldBeCalledTimes(1);
        $repositoryMock->findOneBy(['email' => 'john.doe@example.com'])
            ->willReturn($userMock->reveal())
            ->shouldBeCalledTimes(1);

        $this->passwordTokenManagerMock->createPasswordToken($userMock->reveal())
            ->willReturn($this->prophesize(AbstractPasswordToken::class))
            ->shouldBeCalledTimes(1);
        $this->dispatcherMock->dispatch(
            ForgotPasswordEvent::CREATE_TOKEN,
            Argument::type('CoopTilleuls\ForgotPasswordBundle\Event\ForgotPasswordEvent')
        )->shouldBeCalledTimes(1);

        $manager = new ForgotPasswordManager(
            $this->tokenStorageMock->reveal(),
            $this->requestStackMock->reveal(),
            $this->passwordTokenManagerMock->reveal(),
            $this->dispatcherMock->reveal(),
            $this->doctrineMock->reveal(),
            'AppBundle\Entity\User',
            'email'
        );
        $this->assertTrue($manager->resetPassword());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testUpdatePasswordAccessDeniedException()
    {
        $tokenMock = $this->prophesize(TokenInterface::class);
        $userMock = $this->prophesize(UserInterface::class);
        $passwordTokenMock = $this->prophesize(AbstractPasswordToken::class);

        $this->tokenStorageMock->getToken()->willReturn($tokenMock->reveal())->shouldBeCalledTimes(1);
        $tokenMock->getUser()->willReturn($userMock->reveal())->shouldBeCalledTimes(1);

        $manager = new ForgotPasswordManager(
            $this->tokenStorageMock->reveal(),
            $this->requestStackMock->reveal(),
            $this->passwordTokenManagerMock->reveal(),
            $this->dispatcherMock->reveal(),
            $this->doctrineMock->reveal(),
            'AppBundle\Entity\User',
            'email'
        );
        $manager->updatePassword($passwordTokenMock->reveal());
    }

    public function testUpdatePasswordNoRequestParameter()
    {
        $tokenMock = $this->prophesize(TokenInterface::class);
        $requestMock = $this->prophesize(Request::class);
        $passwordTokenMock = $this->prophesize(AbstractPasswordToken::class);

        $this->tokenStorageMock->getToken()->willReturn($tokenMock->reveal())->shouldBeCalledTimes(1);
        $tokenMock->getUser()->shouldBeCalledTimes(1);

        $this->requestStackMock->getCurrentRequest()->willReturn($requestMock->reveal())->shouldBeCalledTimes(1);
        $requestMock->get('password')->shouldBeCalledTimes(1);

        $manager = new ForgotPasswordManager(
            $this->tokenStorageMock->reveal(),
            $this->requestStackMock->reveal(),
            $this->passwordTokenManagerMock->reveal(),
            $this->dispatcherMock->reveal(),
            $this->doctrineMock->reveal(),
            'AppBundle\Entity\User',
            'email'
        );
        $this->assertFalse($manager->updatePassword($passwordTokenMock->reveal()));
    }

    public function testUpdatePassword()
    {
        $tokenMock = $this->prophesize(TokenInterface::class);
        $requestMock = $this->prophesize(Request::class);
        $passwordTokenMock = $this->prophesize(AbstractPasswordToken::class);

        $this->tokenStorageMock->getToken()->willReturn($tokenMock->reveal())->shouldBeCalledTimes(1);
        $tokenMock->getUser()->shouldBeCalledTimes(1);

        $this->requestStackMock->getCurrentRequest()->willReturn($requestMock->reveal())->shouldBeCalledTimes(1);
        $requestMock->get('password')->willReturn('P4$$w0rd')->shouldBeCalledTimes(1);

        $this->dispatcherMock->dispatch(ForgotPasswordEvent::UPDATE_PASSWORD, Argument::that(function ($event) {
            return $event instanceof ForgotPasswordEvent && null !== $event->getPassword();
        }))->shouldBeCalledTimes(1);

        $this->entityManagerMock->remove($passwordTokenMock->reveal())->shouldBeCalledTimes(1);
        $this->entityManagerMock->flush()->shouldBeCalledTimes(1);

        $manager = new ForgotPasswordManager(
            $this->tokenStorageMock->reveal(),
            $this->requestStackMock->reveal(),
            $this->passwordTokenManagerMock->reveal(),
            $this->dispatcherMock->reveal(),
            $this->doctrineMock->reveal(),
            'AppBundle\Entity\User',
            'email'
        );
        $this->assertTrue($manager->updatePassword($passwordTokenMock->reveal()));
    }
}
