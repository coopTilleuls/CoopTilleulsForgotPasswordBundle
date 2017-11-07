<?php

namespace CoopTilleuls\ForgotPasswordBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class NoParametersException extends HttpException implements JsonHttpExceptionInterface
{
    public function __construct()
    {
        parent::__construct(400, 'No parameters send.');
    }
}
