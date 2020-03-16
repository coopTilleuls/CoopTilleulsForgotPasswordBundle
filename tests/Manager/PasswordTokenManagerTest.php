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
use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\ManagerInterface;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class PasswordTokenManagerTest extends TestCase
{
    /**
     * @var PasswordTokenManager
     */
    private $manager;
    private $managerMock;
    private $userMock;
    private $tokenMock;

    protected function setUp(): void
    {
        $this->managerMock = $this->prophesize(ManagerInterface::class);
        $this->userMock = $this->prophesize(UserInterface::class);
        $this->tokenMock = $this->prophesize(AbstractPasswordToken::class);

        $this->manager = new PasswordTokenManager(
            $this->managerMock->reveal(),
            PasswordToken::class,
            '1 day',
            'user'
        );
    }

    public function testCreatePasswordToken()
    {
        $this->managerMock->persist(Argument::that(function ($object) {
            return $object instanceof AbstractPasswordToken
                   && '2016-10-11 10:00:00' === $object->getExpiresAt()->format('Y-m-d H:i:s')
                   && preg_match('/^[A-z\d]{50}$/', $object->getToken())
                   && $this->userMock->reveal() === $object->getUser()
                ;
        }))->shouldBeCalledTimes(1);

        $this->manager->createPasswordToken($this->userMock->reveal(), new \DateTime('2016-10-11 10:00:00'));
    }

    public function testFindOneByToken()
    {
        $this->managerMock->findOneBy(PasswordToken::class, ['token' => 'foo'])->willReturn('bar')->shouldBeCalledTimes(1);

        $this->assertEquals('bar', $this->manager->findOneByToken('foo'));
    }

    public function testFindOneByUser()
    {
        $this->managerMock->findOneBy(PasswordToken::class, ['user' => $this->userMock->reveal()])->willReturn('bar')->shouldBeCalledTimes(1);

        $this->assertEquals('bar', $this->manager->findOneByUser($this->userMock->reveal()));
    }
}

final class PasswordToken extends AbstractPasswordToken
{
    private $user;

    public function getId()
    {
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }
}
