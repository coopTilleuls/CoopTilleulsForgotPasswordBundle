<?php

namespace CoopTilleuls\ForgotPasswordBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class UserNotFoundHttpException extends HttpException implements JsonHttpExceptionInterface
{
    public function __construct($fieldName, $value)
    {
        parent::__construct(400, sprintf('User with field "%s" equal to "%s" cannot be found.', $fieldName, $value));
    }
}
