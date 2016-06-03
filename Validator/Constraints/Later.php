<?php

namespace CoopTilleuls\ForgotPasswordBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class Later extends Constraint
{
    public $message = 'Date is earlier or equal than today.';
}
