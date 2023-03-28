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

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\ManagerInterface;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderChainInterface;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use CoopTilleuls\ForgotPasswordBundle\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class PasswordTokenManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var PasswordTokenManager
     */
    private $manager;
    private $managerMock;
    private $userMock;
    private $tokenMock;
    private $providerChainMock;
    private $providerMock;

    protected function setUp(): void
    {
        $this->managerMock = $this->prophesize(ManagerInterface::class);
        $this->userMock = $this->prophesize(UserInterface::class);
        $this->tokenMock = $this->prophesize(AbstractPasswordToken::class);
        $this->providerChainMock = $this->prophesize(ProviderChainInterface::class);
        $this->providerMock = $this->prophesize(ProviderInterface::class);

        $this->manager = new PasswordTokenManager($this->providerChainMock->reveal());
    }

    public function testCreatePasswordToken(): void
    {
        $this->managerMock->persist(Argument::that(function ($object) {
            return $object instanceof AbstractPasswordToken
                   && '2016-10-11 10:00:00' === $object->getExpiresAt()->format('Y-m-d H:i:s')
                   && preg_match('/^[A-z\d]{50}$/', $object->getToken())
                   && $this->userMock->reveal() === $object->getUser();
        }))->shouldBeCalledOnce();

        $this->providerChainMock->get()->willReturn($this->providerMock)->shouldBeCalledOnce();
        $this->providerMock->getPasswordTokenClass()->willReturn(PasswordToken::class)->shouldBeCalledOnce();
        $this->providerMock->getManager()->willReturn($this->managerMock)->shouldBeCalledOnce();

        $this->manager->createPasswordToken($this->userMock->reveal(), new \DateTime('2016-10-11 10:00:00'));
    }

    public function testFindOneByToken(): void
    {
        $this->managerMock->findOneBy(PasswordToken::class, ['token' => 'foo'])->willReturn('bar')->shouldBeCalledOnce();

        $this->providerChainMock->get()->willReturn($this->providerMock)->shouldBeCalledOnce();
        $this->providerMock->getPasswordTokenClass()->willReturn(PasswordToken::class)->shouldBeCalledOnce();
        $this->providerMock->getManager()->willReturn($this->managerMock)->shouldBeCalledOnce();

        $this->assertEquals('bar', $this->manager->findOneByToken('foo'));
    }

    public function testFindOneByUser(): void
    {
        $this->managerMock->findOneBy(PasswordToken::class, ['user' => $this->userMock->reveal()])->willReturn('bar')->shouldBeCalledOnce();

        $this->providerChainMock->get()->willReturn($this->providerMock)->shouldBeCalledOnce();
        $this->providerMock->getPasswordTokenClass()->willReturn(PasswordToken::class)->shouldBeCalledOnce();
        $this->providerMock->getPasswordTokenUserField()->willReturn('user')->shouldBeCalledOnce();
        $this->providerMock->getManager()->willReturn($this->managerMock)->shouldBeCalledOnce();

        $this->assertEquals('bar', $this->manager->findOneByUser($this->userMock->reveal()));
    }
}

final class PasswordToken extends AbstractPasswordToken
{
    private $user;

    public function getId(): void
    {
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user): void
    {
        $this->user = $user;
    }
}
