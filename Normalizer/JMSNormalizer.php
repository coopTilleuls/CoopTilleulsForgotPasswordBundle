<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoopTilleuls\ForgotPasswordBundle\Normalizer;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use JMS\Serializer\ArrayTransformerInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class JMSNormalizer implements NormalizerInterface
{
    /**
     * @var ArrayTransformerInterface
     */
    private $normalizer;

    /**
     * @param ArrayTransformerInterface $normalizer
     */
    public function __construct(ArrayTransformerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(AbstractPasswordToken $object, $format, array $context = [])
    {
        return $this->normalizer->toArray($object);
    }
}
