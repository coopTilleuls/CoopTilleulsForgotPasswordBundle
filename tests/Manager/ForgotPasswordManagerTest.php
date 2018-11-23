<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ForgotPasswordBundle\Manager;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Event\ForgotPasswordEvent;
use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\ManagerInterface;
use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class ForgotPasswordManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ForgotPasswordManager
     */
    private $manager;
    private $passwordManagerMock;
    private $eventDispatcherMock;
    private $managerMock;
    private $userMock;
    private $tokenMock;
    private $countableMock;

    protected function setUp()
    {
        $this->passwordManagerMock = $this->prophesize(PasswordTokenManager::class);
        $this->eventDispatcherMock = $this->prophesize(EventDispatcherInterface::class);
        $this->managerMock = $this->prophesize(ManagerInterface::class);
        $this->userMock = $this->prophesize(UserInterface::class);
        $this->tokenMock = $this->prophesize(AbstractPasswordToken::class);
        $this->countableMock = $this->prophesize(\Countable::class);

        $this->manager = new ForgotPasswordManager(
            $this->passwordManagerMock->reveal(),
            $this->eventDispatcherMock->reveal(),
            $this->managerMock->reveal(),
            'App\Entity\User'
        );
    }

    public function testResetPasswordNotUser()
    {
        $this->managerMock->findOneBy('App\Entity\User', ['email' => 'foo@example.com'])->shouldBeCalledTimes(1);
        $this->passwordManagerMock->findOneByUser(Argument::any())->shouldNotBeCalled();

        $this->manager->resetPassword('email', 'foo@example.com');
    }

    public function testResetPasswordWithNoPreviousToken()
    {
        $tokenMock = $this->prophesize(AbstractPasswordToken::class);

        $this->managerMock->findOneBy('App\Entity\User', ['email' => 'foo@example.com'])->willReturn($this->userMock->reveal())->shouldBeCalledTimes(1);
        $this->passwordManagerMock->findOneByUser($this->userMock->reveal())->willReturn(null)->shouldBeCalledTimes(1);
        $this->passwordManagerMock->createPasswordToken($this->userMock->reveal())->willReturn($tokenMock->reveal())->shouldBeCalledTimes(1);
        $this->eventDispatcherMock->dispatch(ForgotPasswordEvent::CREATE_TOKEN, Argument::that(function ($event) use ($tokenMock) {
            return $event instanceof ForgotPasswordEvent && is_null($event->getPassword()) && $tokenMock->reveal() === $event->getPasswordToken();
        }))->shouldBeCalledTimes(1);

        $this->manager->resetPassword('email', 'foo@example.com');
    }

    public function testResetPasswordWithExpiredPreviousToken()
    {
        $tokenMock = $this->prophesize(AbstractPasswordToken::class);

        $this->managerMock->findOneBy('App\Entity\User', ['email' => 'foo@example.com'])->willReturn($this->userMock->reveal())->shouldBeCalledTimes(1);
        $this->passwordManagerMock->findOneByUser($this->userMock->reveal())->willReturn($this->tokenMock->reveal())->shouldBeCalledTimes(1);
        $this->tokenMock->isExpired()->willReturn(true)->shouldBeCalledTimes(1);
        $this->passwordManagerMock->createPasswordToken($this->userMock->reveal())->willReturn($tokenMock->reveal())->shouldBeCalledTimes(1);
        $this->eventDispatcherMock->dispatch(ForgotPasswordEvent::CREATE_TOKEN, Argument::that(function ($event) use ($tokenMock) {
            return $event instanceof ForgotPasswordEvent && is_null($event->getPassword()) && $tokenMock->reveal() === $event->getPasswordToken();
        }))->shouldBeCalledTimes(1);

        $this->manager->resetPassword('email', 'foo@example.com');
    }

    /**
     * @see https://github.com/coopTilleuls/CoopTilleulsForgotPasswordBundle/issues/37
     */
    public function testResetPasswordWithUnexpiredTokenHttp()
    {
        $tokenMock = $this->prophesize(AbstractPasswordToken::class);

        $tokenMock
            ->isExpired()
            ->willReturn(false)
            ->shouldBeCalledTimes(1)
        ;

        $token = $tokenMock->reveal();

        $this->managerMock->findOneBy('App\Entity\User', ['email' => 'foo@example.com'])->willReturn($this->userMock->reveal())->shouldBeCalledTimes(1);
        $this->passwordManagerMock->findOneByUser($this->userMock->reveal())->willReturn($token)->shouldBeCalledTimes(1);

        $this->eventDispatcherMock->dispatch(ForgotPasswordEvent::CREATE_TOKEN, Argument::that(function ($event) use ($token) {
            return $event instanceof ForgotPasswordEvent && is_null($event->getPassword()) && $token === $event->getPasswordToken();
        }))->shouldBeCalledTimes(1);

        $this->manager->resetPassword('foo@example.com');
    }

    public function testUpdatePassword()
    {
        $token = $this->tokenMock->reveal();

        $this->eventDispatcherMock->dispatch(ForgotPasswordEvent::UPDATE_PASSWORD, Argument::that(function ($event) use ($token) {
            return $event instanceof ForgotPasswordEvent && 'bar' === $event->getPassword() && $token === $event->getPasswordToken();
        }))->shouldBeCalledTimes(1);
        $this->managerMock->remove($token)->shouldBeCalledTimes(1);

        $this->manager->updatePassword($token, 'bar');
    }
}
