<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoopTilleuls\ForgotPasswordBundle\Bridge;

use JMS\Serializer\ArrayTransformerInterface;
use JMS\Serializer\SerializerInterface as JMSSerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
class Normalizer
{
    /**
     * @var NormalizerInterface|ArrayTransformerInterface
     */
    private $normalizer;

    /**
     * @param NormalizerInterface|ArrayTransformerInterface $normalizer
     */
    public function __construct($normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * @param mixed $object
     * @param null  $format
     * @param array $context
     *
     * @return array
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return $this->normalizer instanceof JMSSerializerInterface ? $this->normalizer->toArray($object) : $this->normalizer->normalize($object, $format, $context);
    }
}
