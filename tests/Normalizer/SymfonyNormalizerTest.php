<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ForgotPasswordBundle\Normalizer;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Normalizer\SymfonyNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class SymfonyNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testNormalize()
    {
        $normalizerMock = $this->prophesize(NormalizerInterface::class);
        $passwordTokenMock = $this->prophesize(AbstractPasswordToken::class);

        $normalizerMock->normalize($passwordTokenMock, 'json', [])->willReturn('foo')->shouldBeCalledTimes(1);

        $normalizer = new SymfonyNormalizer($normalizerMock->reveal());
        $this->assertEquals('foo', $normalizer->normalize($passwordTokenMock->reveal(), 'json'));
    }
}
