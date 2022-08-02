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

namespace CoopTilleuls\ForgotPasswordBundle\Tests\Normalizer;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Normalizer\JMSNormalizer;
use CoopTilleuls\ForgotPasswordBundle\Tests\ProphecyTrait;
use JMS\Serializer\ArrayTransformerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class JMSNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testNormalize(): void
    {
        $normalizerMock = $this->prophesize(ArrayTransformerInterface::class);
        $passwordTokenMock = $this->prophesize(AbstractPasswordToken::class);

        $normalizerMock->toArray($passwordTokenMock)->willReturn(['foo'])->shouldBeCalledOnce();

        $normalizer = new JMSNormalizer($normalizerMock->reveal());
        $this->assertEquals(['foo'], $normalizer->normalize($passwordTokenMock->reveal(), 'json'));
    }
}
