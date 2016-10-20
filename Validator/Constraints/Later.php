<?php

/*
 * This file is part of the ForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoopTilleuls\ForgotPasswordBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class Later extends Constraint
{
    public $message = 'Date is earlier or equal than today.';
}
