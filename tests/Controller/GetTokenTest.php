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

use CoopTilleuls\ForgotPasswordBundle\Controller\GetToken;
use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Normalizer\NormalizerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class GetTokenTest extends TestCase
{
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
        $this->normalizerMock = $this->prophesize(NormalizerInterface::class);
        $this->tokenMock = $this->prophesize(AbstractPasswordToken::class);
    }

    public function testGetTokenAction()
    {
        $this->normalizerMock->normalize($this->tokenMock->reveal(), 'json', ['groups' => ['foo']])
            ->willReturn(['foo' => 'bar'])
            ->shouldBeCalledTimes(1);
        $controller = new GetToken($this->normalizerMock->reveal(), ['foo']);
        $response = $controller($this->tokenMock->reveal());
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(json_encode(['foo' => 'bar']), $response->getContent());
    }
}
