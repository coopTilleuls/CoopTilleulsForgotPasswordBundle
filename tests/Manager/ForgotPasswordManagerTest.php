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

use App\Entity\Admin;
use App\Entity\PasswordAdminToken;
use App\Entity\PasswordToken;
use App\Entity\User;
use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Event\CreateTokenEvent;
use CoopTilleuls\ForgotPasswordBundle\Event\ForgotPasswordEvent;
use CoopTilleuls\ForgotPasswordBundle\Event\UpdatePasswordEvent;
use CoopTilleuls\ForgotPasswordBundle\Event\UserNotFoundEvent;
use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\ManagerInterface;
use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use CoopTilleuls\ForgotPasswordBundle\Provider\Provider;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderFactory;
use CoopTilleuls\ForgotPasswordBundle\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as ContractsEventDispatcherInterface;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class ForgotPasswordManagerTest extends TestCase
{
    use ProphecyTrait;

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
    private $providerFactoryMock;

    protected function setUp(): void
    {
        $this->passwordManagerMock = $this->prophesize(PasswordTokenManager::class);
        $this->eventDispatcherMock = $this->prophesize(EventDispatcherInterface::class);
        $this->managerMock = $this->prophesize(ManagerInterface::class);
        $this->userMock = $this->prophesize(UserInterface::class);
        $this->tokenMock = $this->prophesize(AbstractPasswordToken::class);
        $this->countableMock = $this->prophesize(\Countable::class);
        $this->providerFactoryMock = $this->prophesize(ProviderFactory::class);

        $this->manager = new ForgotPasswordManager(
            $this->passwordManagerMock->reveal(),
            $this->eventDispatcherMock->reveal(),
            $this->managerMock->reveal(),
            $this->providerFactoryMock->reveal()
        );
    }

    public function testResetPasswordNotUser(): void
    {
        $this->providerFactoryMock->getDefault()->willReturn(self::providerDataProvider()['customer'])->shouldBeCalledOnce();

        $this->managerMock->findOneBy(User::class, ['email' => 'foo@example.com'])->shouldBeCalledOnce();
        if ($this->eventDispatcherMock->reveal() instanceof ContractsEventDispatcherInterface) {
            $this->eventDispatcherMock->dispatch(Argument::that(function ($event) {
                return $event instanceof UserNotFoundEvent && ['email' => 'foo@example.com'] === $event->getContext();
            }))->shouldBeCalledOnce();
        } else {
            $this->eventDispatcherMock->dispatch(UserNotFoundEvent::USER_NOT_FOUND, Argument::that(function ($event) {
                return $event instanceof UserNotFoundEvent && ['email' => 'foo@example.com'] === $event->getContext();
            }))->shouldBeCalledOnce();
        }

        $this->passwordManagerMock->findOneByUser(Argument::any())->shouldNotBeCalled();

        $expiredAt = new \DateTime(self::providerDataProvider()['customer']->getPasswordTokenExpiredIn());
        $expiredAt->setTime((int) $expiredAt->format('H'), (int) $expiredAt->format('m'), (int) $expiredAt->format('s'), 0);

        $this->manager->resetPassword('email', 'foo@example.com');
    }

    public function testResetPasswordWithNoPreviousToken(): void
    {
        $tokenMock = $this->prophesize(AbstractPasswordToken::class);

        $expiredAt = new \DateTime(self::providerDataProvider()['customer']->getPasswordTokenExpiredIn());
        $expiredAt->setTime((int) $expiredAt->format('H'), (int) $expiredAt->format('m'), (int) $expiredAt->format('s'), 0);

        $this->providerFactoryMock->get('customer')->willReturn(self::providerDataProvider()['customer'])->shouldBeCalledOnce();

        $this->managerMock->findOneBy(User::class, ['email' => 'foo@example.com'])->willReturn($this->userMock->reveal())->shouldBeCalledOnce();
        $this->passwordManagerMock->findOneByUser(PasswordToken::class, $this->userMock->reveal())->willReturn(null)->shouldBeCalledOnce();
        $this->passwordManagerMock->createPasswordToken($this->userMock->reveal(), $expiredAt, 'customer')->willReturn($tokenMock->reveal())->shouldBeCalledOnce();

        if ($this->eventDispatcherMock->reveal() instanceof ContractsEventDispatcherInterface) {
            $this->eventDispatcherMock->dispatch(Argument::that(function ($event) use ($tokenMock) {
                return $event instanceof CreateTokenEvent && null === $event->getPassword() && $tokenMock->reveal() === $event->getPasswordToken();
            }))->shouldBeCalledOnce();
        } else {
            $this->eventDispatcherMock->dispatch(ForgotPasswordEvent::CREATE_TOKEN, Argument::that(function ($event) use ($tokenMock) {
                return $event instanceof CreateTokenEvent && null === $event->getPassword() && $tokenMock->reveal() === $event->getPasswordToken();
            }))->shouldBeCalledOnce();
        }

        $this->manager->resetPassword('email', 'foo@example.com', 'customer');
    }

    public function testResetPasswordWithExpiredPreviousToken(): void
    {
        $tokenMock = $this->prophesize(AbstractPasswordToken::class);
        $this->tokenMock->isExpired()->willReturn(true)->shouldBeCalledOnce();

        $expiredAt = new \DateTime(self::providerDataProvider()['customer']->getPasswordTokenExpiredIn());
        $expiredAt->setTime((int) $expiredAt->format('H'), (int) $expiredAt->format('m'), (int) $expiredAt->format('s'), 0);

        $this->providerFactoryMock->get('customer')->willReturn(self::providerDataProvider()['customer'])->shouldBeCalledOnce();

        $this->managerMock->findOneBy(User::class, ['email' => 'foo@example.com'])->willReturn($this->userMock->reveal())->shouldBeCalledOnce();
        $this->passwordManagerMock->findOneByUser(PasswordToken::class, $this->userMock->reveal())->willReturn($this->tokenMock->reveal())->shouldBeCalledOnce();
        $this->passwordManagerMock->createPasswordToken($this->userMock->reveal(), $expiredAt, 'customer')->willReturn($tokenMock->reveal())->shouldBeCalledOnce();

        if ($this->eventDispatcherMock->reveal() instanceof ContractsEventDispatcherInterface) {
            $this->eventDispatcherMock->dispatch(Argument::that(function ($event) use ($tokenMock) {
                return $event instanceof CreateTokenEvent && null === $event->getPassword() && $tokenMock->reveal() === $event->getPasswordToken();
            }))->shouldBeCalledOnce();
        } else {
            $this->eventDispatcherMock->dispatch(ForgotPasswordEvent::CREATE_TOKEN, Argument::that(function ($event) use ($tokenMock) {
                return $event instanceof CreateTokenEvent && null === $event->getPassword() && $tokenMock->reveal() === $event->getPasswordToken();
            }))->shouldBeCalledOnce();
        }

        $this->manager->resetPassword('email', 'foo@example.com', 'customer');
    }

    /**
     * @see https://github.com/coopTilleuls/CoopTilleulsForgotPasswordBundle/issues/37
     */
    public function testResetPasswordWithUnexpiredTokenHttp(): void
    {
        $tokenMock = $this->prophesize(AbstractPasswordToken::class);

        $tokenMock
            ->isExpired()
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $token = $tokenMock->reveal();
        $this->providerFactoryMock->getDefault()->willReturn(self::providerDataProvider()['customer'])->shouldBeCalledOnce();
        $this->managerMock->findOneBy(User::class, ['email' => 'foo@example.com'])->willReturn($this->userMock->reveal())->shouldBeCalledOnce();
        $this->passwordManagerMock->findOneByUser(PasswordToken::class, $this->userMock->reveal())->willReturn($token)->shouldBeCalledOnce();

        if ($this->eventDispatcherMock->reveal() instanceof ContractsEventDispatcherInterface) {
            $this->eventDispatcherMock->dispatch(Argument::that(function ($event) use ($tokenMock) {
                return $event instanceof CreateTokenEvent && null === $event->getPassword() && $tokenMock->reveal() === $event->getPasswordToken();
            }))->shouldBeCalledOnce();
        } else {
            $this->eventDispatcherMock->dispatch(ForgotPasswordEvent::CREATE_TOKEN, Argument::that(function ($event) use ($tokenMock) {
                return $event instanceof CreateTokenEvent && null === $event->getPassword() && $tokenMock->reveal() === $event->getPasswordToken();
            }))->shouldBeCalledOnce();
        }

        $this->manager->resetPassword('email', 'foo@example.com');
    }

    public function testUpdatePassword(): void
    {
        $token = $this->tokenMock->reveal();

        if ($this->eventDispatcherMock->reveal() instanceof ContractsEventDispatcherInterface) {
            $this->eventDispatcherMock->dispatch(Argument::that(function ($event) use ($token) {
                return $event instanceof UpdatePasswordEvent && 'bar' === $event->getPassword() && $token === $event->getPasswordToken();
            }))->shouldBeCalledOnce();
        } else {
            $this->eventDispatcherMock->dispatch(ForgotPasswordEvent::UPDATE_PASSWORD, Argument::that(function ($event) use ($token) {
                return $event instanceof UpdatePasswordEvent && 'bar' === $event->getPassword() && $token === $event->getPasswordToken();
            }))->shouldBeCalledOnce();
        }
        $this->managerMock->remove($token)->shouldBeCalledOnce();

        $this->manager->updatePassword($token, 'bar');
    }

    private static function providerDataProvider(): array
    {
        return [
            'customer' => new Provider(
                PasswordToken::class,
                '+1 day',
                'user',
                User::class,
                [],
                'email',
                'password',
                ['email', 'password'],
                true
            ),
            'admin' => new Provider(
                PasswordAdminToken::class,
                '+1 hour',
                'admin',
                Admin::class,
                [],
                'username',
                'encryptPassword',
                ['email', 'password'],
            ), ];
    }
}
