<?php

namespace CoopTilleuls\ForgotPasswordBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class LaterValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var Later $constraint */
        if ((new \DateTime()) > $value) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
