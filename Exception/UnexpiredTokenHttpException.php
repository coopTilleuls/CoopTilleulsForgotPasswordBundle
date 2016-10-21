<?php

namespace CoopTilleuls\ForgotPasswordBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class UnexpiredTokenHttpException extends HttpException implements JsonHttpExceptionInterface
{
    public function __construct()
    {
        parent::__construct(400, 'An unexpired token already exists for this user.');
    }
}
