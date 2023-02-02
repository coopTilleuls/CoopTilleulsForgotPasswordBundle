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
use CoopTilleuls\ForgotPasswordBundle\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class SymfonyNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testNormalize(): void
    {
        $normalizerMock = $this->prophesize(NormalizerInterface::class);
        $passwordTokenMock = $this->prophesize(AbstractPasswordToken::class);

        $normalizerMock->normalize($passwordTokenMock, 'json', [])->willReturn('foo')->shouldBeCalledOnce();

        $normalizer = new SymfonyNormalizer($normalizerMock->reveal());
        $this->assertEquals('foo', $normalizer->normalize($passwordTokenMock->reveal(), 'json'));
    }
}
