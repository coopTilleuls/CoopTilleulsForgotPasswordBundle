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
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use CoopTilleuls\ForgotPasswordBundle\TokenGenerator\TokenGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
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
    private $providerMock;
    private $tokenGeneratorMock;

    protected function setUp(): void
    {
        $this->managerMock = $this->createMock(ManagerInterface::class);
        $this->userMock = $this->createMock(UserInterface::class);
        $this->tokenMock = $this->createMock(AbstractPasswordToken::class);
        $this->providerMock = $this->createMock(ProviderInterface::class);
        $this->tokenGeneratorMock = $this->createMock(TokenGeneratorInterface::class);

        $this->manager = new PasswordTokenManager($this->tokenGeneratorMock);
    }

    public function testCreatePasswordToken(): void
    {
        $this->managerMock->expects($this->once())->method('persist')->with($this->callback(fn ($object) => $object instanceof AbstractPasswordToken
               && '2016-10-11 10:00:00' === $object->getExpiresAt()->format('Y-m-d H:i:s')
               && '12345' === $object->getToken()
               && $this->userMock === $object->getUser()));

        $this->providerMock->expects($this->once())->method('getPasswordTokenClass')->willReturn(PasswordToken::class);
        $this->providerMock->expects($this->once())->method('getManager')->willReturn($this->managerMock);
        $this->tokenGeneratorMock->expects($this->once())->method('generate')->willReturn('12345');

        $this->manager->createPasswordToken($this->userMock, $this->providerMock, new \DateTime('2016-10-11 10:00:00'));
    }

    public function testCreatePasswordTokenWithoutExpirationDate(): void
    {
        $this->managerMock->expects($this->once())->method('persist')->with($this->callback(fn ($object) => $object instanceof AbstractPasswordToken
            && '12345' === $object->getToken()
            && $this->userMock === $object->getUser()));

        $this->providerMock->expects($this->once())->method('getPasswordTokenClass')->willReturn(PasswordToken::class);
        $this->providerMock->expects($this->once())->method('getManager')->willReturn($this->managerMock);
        $this->tokenGeneratorMock->expects($this->once())->method('generate')->willReturn('12345');

        $this->manager->createPasswordToken($this->userMock, $this->providerMock);
    }

    public function testFindOneByToken(): void
    {
        $this->managerMock->expects($this->once())->method('findOneBy')->with(PasswordToken::class, ['token' => 'foo'])->willReturn('bar');

        $this->providerMock->expects($this->once())->method('getPasswordTokenClass')->willReturn(PasswordToken::class);
        $this->providerMock->expects($this->once())->method('getManager')->willReturn($this->managerMock);

        $this->assertEquals('bar', $this->manager->findOneByToken('foo', $this->providerMock));
    }

    public function testFindOneByUser(): void
    {
        $this->managerMock->expects($this->once())->method('findOneBy')->with(PasswordToken::class, ['user' => $this->userMock])->willReturn('bar');

        $this->providerMock->expects($this->once())->method('getPasswordTokenClass')->willReturn(PasswordToken::class);
        $this->providerMock->expects($this->once())->method('getPasswordTokenUserField')->willReturn('user');
        $this->providerMock->expects($this->once())->method('getManager')->willReturn($this->managerMock);

        $this->assertEquals('bar', $this->manager->findOneByUser($this->userMock, $this->providerMock));
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
