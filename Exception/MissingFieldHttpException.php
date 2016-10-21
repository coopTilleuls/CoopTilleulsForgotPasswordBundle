<?php

namespace CoopTilleuls\ForgotPasswordBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class MissingFieldHttpException extends HttpException implements JsonHttpExceptionInterface
{
    public function __construct($fieldName)
    {
        parent::__construct(400, sprintf('Parameter "%s" is missing.', $fieldName));
    }
}
