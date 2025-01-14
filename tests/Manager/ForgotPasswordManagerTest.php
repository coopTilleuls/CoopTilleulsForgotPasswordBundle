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

namespace CoopTilleuls\ForgotPasswordBundle\Tests\Manager;

use App\Entity\User;
use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Event\CreateTokenEvent;
use CoopTilleuls\ForgotPasswordBundle\Event\ForgotPasswordEvent;
use CoopTilleuls\ForgotPasswordBundle\Event\UpdatePasswordEvent;
use CoopTilleuls\ForgotPasswordBundle\Event\UserNotFoundEvent;
use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\ManagerInterface;
use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderChainInterface;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as ContractsEventDispatcherInterface;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class ForgotPasswordManagerTest extends TestCase
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
    private $providerChainMock;
    private $providerMock;

    protected function setUp(): void
    {
        $this->passwordManagerMock = $this->createMock(PasswordTokenManager::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $this->managerMock = $this->createMock(ManagerInterface::class);
        $this->userMock = $this->createMock(UserInterface::class);
        $this->tokenMock = $this->createMock(AbstractPasswordToken::class);
        $this->countableMock = $this->createMock(\Countable::class);
        $this->providerChainMock = $this->createMock(ProviderChainInterface::class);
        $this->providerMock = $this->createMock(ProviderInterface::class);

        $this->manager = new ForgotPasswordManager(
            $this->passwordManagerMock,
            $this->eventDispatcherMock,
            $this->providerChainMock
        );
    }

    public function testResetPasswordNotUser(): void
    {
        $this->providerMock->expects($this->once())->method('getManager')->willReturn($this->managerMock);
        $this->providerMock->expects($this->once())->method('getUserClass')->willReturn(User::class);
        $this->managerMock->expects($this->once())->method('findOneBy')->with(User::class, ['email' => 'foo@example.com']);
        if ($this->eventDispatcherMock instanceof ContractsEventDispatcherInterface) {
            $this->eventDispatcherMock->expects($this->once())->method('dispatch')->with($this->callback(fn ($event) => $event instanceof UserNotFoundEvent && ['email' => 'foo@example.com'] === $event->getContext()));
        } else {
            $this->eventDispatcherMock->expects($this->once())->method('dispatch')->with(UserNotFoundEvent::USER_NOT_FOUND, $this->callback(fn ($event) => $event instanceof UserNotFoundEvent && ['email' => 'foo@example.com'] === $event->getContext()));
        }

        $this->passwordManagerMock->expects($this->never())->method('findOneByUser')->with(self::any(), $this->providerMock);

        $this->manager->resetPassword('email', 'foo@example.com', $this->providerMock);
    }

    public function testResetPasswordWithNoPreviousToken(): void
    {
        $this->providerMock->expects($this->once())->method('getManager')->willReturn($this->managerMock);
        $this->providerMock->expects($this->once())->method('getUserClass')->willReturn(User::class);
        $this->providerMock->expects($this->once())->method('getPasswordTokenExpiredIn')->willReturn('+1 day');
        $this->managerMock->expects($this->once())->method('findOneBy')->with(User::class, ['email' => 'foo@example.com'])->willReturn($this->userMock);
        $this->passwordManagerMock->expects($this->once())->method('findOneByUser')->with($this->userMock, $this->providerMock)->willReturn(null);
        $this->passwordManagerMock->expects($this->once())->method('createPasswordToken')->with($this->userMock, $this->isInstanceOf(\DateTimeInterface::class), $this->providerMock)->willReturn($this->tokenMock);

        if ($this->eventDispatcherMock instanceof ContractsEventDispatcherInterface) {
            $this->eventDispatcherMock->expects($this->once())->method('dispatch')->with($this->callback(fn ($event) => $event instanceof CreateTokenEvent && null === $event->getPassword() && $this->tokenMock === $event->getPasswordToken()));
        } else {
            $this->eventDispatcherMock->expects($this->once())->method('dispatch')->with(ForgotPasswordEvent::CREATE_TOKEN, $this->callback(fn ($event) => $event instanceof CreateTokenEvent && null === $event->getPassword() && $this->tokenMock === $event->getPasswordToken()));
        }

        $this->manager->resetPassword('email', 'foo@example.com', $this->providerMock);
    }

    public function testResetPasswordWithExpiredPreviousToken(): void
    {
        $this->providerMock->expects($this->once())->method('getManager')->willReturn($this->managerMock);
        $this->providerMock->expects($this->once())->method('getUserClass')->willReturn(User::class);
        $this->providerMock->expects($this->once())->method('getPasswordTokenExpiredIn')->willReturn('+1 day');
        $this->tokenMock->expects($this->once())->method('isExpired')->willReturn(true);
        $this->managerMock->expects($this->once())->method('findOneBy')->with(User::class, ['email' => 'foo@example.com'])->willReturn($this->userMock);
        $this->passwordManagerMock->expects($this->once())->method('findOneByUser')->with($this->userMock, $this->providerMock)->willReturn($this->tokenMock);
        $this->passwordManagerMock->expects($this->once())->method('createPasswordToken')->with($this->userMock, $this->isInstanceOf(\DateTimeInterface::class), $this->providerMock)->willReturn($this->tokenMock);

        if ($this->eventDispatcherMock instanceof ContractsEventDispatcherInterface) {
            $this->eventDispatcherMock->expects($this->once())->method('dispatch')->with($this->callback(fn ($event) => $event instanceof CreateTokenEvent && null === $event->getPassword() && $this->tokenMock === $event->getPasswordToken()));
        } else {
            $this->eventDispatcherMock->expects($this->once())->method('dispatch')->with(ForgotPasswordEvent::CREATE_TOKEN, $this->callback(fn ($event) => $event instanceof CreateTokenEvent && null === $event->getPassword() && $this->tokenMock === $event->getPasswordToken()));
        }

        $this->manager->resetPassword('email', 'foo@example.com', $this->providerMock);
    }

    /**
     * @see https://github.com/coopTilleuls/CoopTilleulsForgotPasswordBundle/issues/37
     */
    public function testResetPasswordWithUnexpiredTokenHttp(): void
    {
        $this->providerMock->expects($this->once())->method('getManager')->willReturn($this->managerMock);
        $this->providerMock->expects($this->once())->method('getUserClass')->willReturn(User::class);
        $this->tokenMock->expects($this->once())->method('isExpired')->willReturn(false);
        $this->managerMock->expects($this->once())->method('findOneBy')->with(User::class, ['email' => 'foo@example.com'])->willReturn($this->userMock);
        $this->passwordManagerMock->expects($this->once())->method('findOneByUser')->with($this->userMock, $this->providerMock)->willReturn($this->tokenMock);

        if ($this->eventDispatcherMock instanceof ContractsEventDispatcherInterface) {
            $this->eventDispatcherMock->expects($this->once())->method('dispatch')->with($this->callback(fn ($event) => $event instanceof CreateTokenEvent && null === $event->getPassword() && $this->tokenMock === $event->getPasswordToken()));
        } else {
            $this->eventDispatcherMock->expects($this->once())->method('dispatch')->with(ForgotPasswordEvent::CREATE_TOKEN, $this->callback(fn ($event) => $event instanceof CreateTokenEvent && null === $event->getPassword() && $this->tokenMock === $event->getPasswordToken()));
        }

        $this->manager->resetPassword('email', 'foo@example.com', $this->providerMock);
    }

    public function testUpdatePassword(): void
    {
        $this->providerMock->expects($this->once())->method('getManager')->willReturn($this->managerMock);

        if ($this->eventDispatcherMock instanceof ContractsEventDispatcherInterface) {
            $this->eventDispatcherMock->expects($this->once())->method('dispatch')->with($this->callback(fn ($event) => $event instanceof UpdatePasswordEvent && 'bar' === $event->getPassword() && $this->tokenMock === $event->getPasswordToken()));
        } else {
            $this->eventDispatcherMock->expects($this->once())->method('dispatch')->with(ForgotPasswordEvent::UPDATE_PASSWORD, $this->callback(fn ($event) => $event instanceof UpdatePasswordEvent && 'bar' === $event->getPassword() && $this->tokenMock === $event->getPasswordToken()));
        }
        $this->managerMock->expects($this->once())->method('remove')->with($this->tokenMock);

        $this->manager->updatePassword($this->tokenMock, 'bar', $this->providerMock);
    }
}
