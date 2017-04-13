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

use CoopTilleuls\ForgotPasswordBundle\Controller\ForgotPasswordController;
use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ForgotPasswordControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ForgotPasswordController
     */
    private $controller;
    private $managerMock;
    private $normalizerMock;
    private $tokenMock;

    protected function setUp()
    {
        $this->managerMock = $this->prophesize(ForgotPasswordManager::class);
        $this->normalizerMock = $this->prophesize(NormalizerInterface::class);
        $this->tokenMock = $this->prophesize(AbstractPasswordToken::class);

        $this->controller = new ForgotPasswordController(
            $this->managerMock->reveal(),
            $this->normalizerMock->reveal(),
            ['foo']
        );
    }

    public function testResetPasswordAction()
    {
        $this->managerMock->resetPassword('foo@example.com')->shouldBeCalledTimes(1);
        $response = $this->controller->resetPasswordAction('foo@example.com');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('', $response->getContent());
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testGetTokenAction()
    {
        $this->normalizerMock->normalize($this->tokenMock->reveal(), 'json', ['groups' => ['foo']])
            ->willReturn(['foo' => 'bar'])
            ->shouldBeCalledTimes(1);
        $response = $this->controller->getTokenAction($this->tokenMock->reveal());
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(json_encode(['foo' => 'bar']), $response->getContent());
    }

    public function testUpdatePasswordAction()
    {
        $this->managerMock->updatePassword($this->tokenMock->reveal(), 'bar')->shouldBeCalledTimes(1);
        $response = $this->controller->updatePasswordAction($this->tokenMock->reveal(), 'bar');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('', $response->getContent());
        $this->assertEquals(204, $response->getStatusCode());
    }
}
