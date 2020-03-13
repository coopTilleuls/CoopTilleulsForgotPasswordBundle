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

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
interface NormalizerInterface
{
    /**
     * @param string $format
     *
     * @return mixed
     */
    public function normalize(AbstractPasswordToken $object, $format, array $context = []);
}
