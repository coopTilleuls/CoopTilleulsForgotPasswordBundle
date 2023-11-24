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

use CoopTilleuls\ForgotPasswordBundle\Controller\GetToken;
use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Normalizer\NormalizerInterface;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
final class GetTokenTest extends TestCase
{
        /**
     * @var ProviderInterface|ObjectProphecy
     */
    private $providerMock;

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
        $this->providerMock = $this->createMock(ProviderInterface::class);
        $this->normalizerMock = $this->createMock(NormalizerInterface::class);
        $this->tokenMock = $this->createMock(AbstractPasswordToken::class);
    }

    public function testGetTokenAction(): void
    {
        $this->providerMock->expects($this->once())->method('getPasswordTokenSerializationGroups')->willReturn(['foo']);
        $this->normalizerMock->expects($this->once())->method('normalize')->with($this->tokenMock, 'json', ['groups' => ['foo']])->willReturn(['foo' => 'bar']);
        $controller = new GetToken($this->normalizerMock);
        $response = $controller($this->tokenMock, $this->providerMock);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(json_encode(['foo' => 'bar']), $response->getContent());
    }
}
