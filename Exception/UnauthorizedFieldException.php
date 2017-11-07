<?php

namespace CoopTilleuls\ForgotPasswordBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class UnauthorizedFieldException extends HttpException implements JsonHttpExceptionInterface
{
    public function __construct($propertyName)
    {
        parent::__construct(400, sprintf('The parameter "%s" is not authorized in your configuration.', $propertyName));
    }
}
