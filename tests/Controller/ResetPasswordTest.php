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

use CoopTilleuls\ForgotPasswordBundle\Controller\ResetPassword;
use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
final class ResetPasswordTest extends TestCase
{
        /**
     * @var ProviderInterface|ObjectProphecy
     */
    private $providerMock;
    /**
     * @var ForgotPasswordManager|ObjectProphecy
     */
    private $managerMock;

    protected function setUp(): void
    {
        $this->providerMock = $this->createMock(ProviderInterface::class);
        $this->managerMock = $this->createMock(ForgotPasswordManager::class);
    }

    public function testResetPasswordAction(): void
    {
        $this->managerMock->expects($this->once())->method('resetPassword')->with('email', 'foo@example.com', $this->providerMock);
        $controller = new ResetPassword($this->managerMock);
        $response = $controller('email', 'foo@example.com', $this->providerMock);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('', $response->getContent());
        $this->assertEquals(204, $response->getStatusCode());
    }
}
