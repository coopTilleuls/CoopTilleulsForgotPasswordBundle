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

namespace CoopTilleuls\ForgotPasswordBundle\Normalizer;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface as SymfonyNormalizerInterface;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class SymfonyNormalizer implements NormalizerInterface
{
    public function __construct(private readonly SymfonyNormalizerInterface $normalizer)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(AbstractPasswordToken $object, $format, array $context = [])
    {
        return $this->normalizer->normalize($object, $format, $context);
    }
}
