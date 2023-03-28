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
use CoopTilleuls\ForgotPasswordBundle\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
final class UpdatePasswordTest extends TestCase
{
    use ProphecyTrait;

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
        $this->providerMock = $this->prophesize(ProviderInterface::class);
        $this->managerMock = $this->prophesize(ForgotPasswordManager::class);
        $this->tokenMock = $this->prophesize(AbstractPasswordToken::class);
    }

    public function testUpdatePasswordAction(): void
    {
        $expiredAt = new \DateTime('+1 day');
        $expiredAt->setTime((int) $expiredAt->format('H'), (int) $expiredAt->format('m'), (int) $expiredAt->format('s'), 0);

        $this->managerMock->updatePassword($this->tokenMock->reveal(), 'bar', $this->providerMock)->shouldBeCalledOnce();
        $controller = new UpdatePassword($this->managerMock->reveal());
        $response = $controller($this->tokenMock->reveal(), 'bar', $this->providerMock->reveal());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('', $response->getContent());
        $this->assertEquals(204, $response->getStatusCode());
    }
}
