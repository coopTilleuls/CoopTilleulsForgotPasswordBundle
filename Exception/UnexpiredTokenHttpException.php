<?php

/*
 * This file is part of the ForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoopTilleuls\ForgotPasswordBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class UnexpiredTokenHttpException extends HttpException implements JsonHttpExceptionInterface
{
    public function __construct()
    {
        parent::__construct(400, 'An unexpired token already exists for this user.');
    }
}
