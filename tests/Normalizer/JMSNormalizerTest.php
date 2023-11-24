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
use JMS\Serializer\ArrayTransformerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class JMSNormalizerTest extends TestCase
{
        public function testNormalize(): void
    {
        $normalizerMock = $this->createMock(ArrayTransformerInterface::class);
        $passwordTokenMock = $this->createMock(AbstractPasswordToken::class);

        $normalizerMock->expects($this->once())->method('toArray')->with($passwordTokenMock)->willReturn(['foo']);

        $normalizer = new JMSNormalizer($normalizerMock);
        $this->assertEquals(['foo'], $normalizer->normalize($passwordTokenMock, 'json'));
    }
}
