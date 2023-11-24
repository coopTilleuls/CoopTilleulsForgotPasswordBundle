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
use CoopTilleuls\ForgotPasswordBundle\Normalizer\SymfonyNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class SymfonyNormalizerTest extends TestCase
{
    public function testNormalize(): void
    {
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        $passwordTokenMock = $this->createMock(AbstractPasswordToken::class);

        $normalizerMock->expects($this->once())->method('normalize')->with($passwordTokenMock, 'json', [])->willReturn('foo');

        $normalizer = new SymfonyNormalizer($normalizerMock);
        $this->assertEquals('foo', $normalizer->normalize($passwordTokenMock, 'json'));
    }
}
