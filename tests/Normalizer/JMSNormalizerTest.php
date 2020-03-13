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
use CoopTilleuls\ForgotPasswordBundle\Normalizer\JMSNormalizer;
use JMS\Serializer\ArrayTransformerInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class JMSNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testNormalize()
    {
        $normalizerMock = $this->prophesize(ArrayTransformerInterface::class);
        $passwordTokenMock = $this->prophesize(AbstractPasswordToken::class);

        $normalizerMock->toArray($passwordTokenMock)->willReturn(['foo'])->shouldBeCalledTimes(1);

        $normalizer = new JMSNormalizer($normalizerMock->reveal());
        $this->assertEquals(['foo'], $normalizer->normalize($passwordTokenMock->reveal(), 'json'));
    }
}
