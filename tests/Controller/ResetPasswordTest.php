<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ForgotPasswordBundle\Controller;

use CoopTilleuls\ForgotPasswordBundle\Controller\ResetPassword;
use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use CoopTilleuls\ForgotPasswordBundle\Normalizer\NormalizerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ResetPasswordTest extends TestCase
{
    /**
     * @var ForgotPasswordManager|ObjectProphecy
     */
    private $managerMock;

    /**
     * @var NormalizerInterface|ObjectProphecy
     */
    private $normalizerMock;

    /**
     * @var AbstractPasswordToken|ObjectProphecy
     */
    private $tokenMock;

    protected function setUp(): void
    {
        $this->managerMock = $this->prophesize(ForgotPasswordManager::class);
        $this->normalizerMock = $this->prophesize(NormalizerInterface::class);
        $this->tokenMock = $this->prophesize(AbstractPasswordToken::class);
    }

    public function testResetPasswordAction()
    {
        $this->managerMock->resetPassword('email', 'foo@example.com')->shouldBeCalledTimes(1);
        $controller = new ResetPassword($this->managerMock->reveal());
        $response = $controller('email', 'foo@example.com');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('', $response->getContent());
        $this->assertEquals(204, $response->getStatusCode());
    }
}
