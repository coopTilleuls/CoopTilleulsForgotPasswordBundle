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

namespace CoopTilleuls\ForgotPasswordBundle\Tests\Controller;

use CoopTilleuls\ForgotPasswordBundle\Controller\UpdatePassword;
use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
final class UpdatePasswordTest extends TestCase
{
        /**
     * @var ProviderInterface|ObjectProphecy
     */
    private $providerMock;
    /**
     * @var ForgotPasswordManager|ObjectProphecy
     */
    private $managerMock;

    /**
     * @var AbstractPasswordToken|ObjectProphecy
     */
    private $tokenMock;

    protected function setUp(): void
    {
        $this->providerMock = $this->createMock(ProviderInterface::class);
        $this->managerMock = $this->createMock(ForgotPasswordManager::class);
        $this->tokenMock = $this->createMock(AbstractPasswordToken::class);
    }

    public function testUpdatePasswordAction(): void
    {
        $expiredAt = new \DateTime('+1 day');
        $expiredAt->setTime((int) $expiredAt->format('H'), (int) $expiredAt->format('m'), (int) $expiredAt->format('s'), 0);

        $this->managerMock->expects($this->once())->method('updatePassword')->with($this->tokenMock, 'bar', $this->providerMock);
        $controller = new UpdatePassword($this->managerMock);
        $response = $controller($this->tokenMock, 'bar', $this->providerMock);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('', $response->getContent());
        $this->assertEquals(204, $response->getStatusCode());
    }
}
