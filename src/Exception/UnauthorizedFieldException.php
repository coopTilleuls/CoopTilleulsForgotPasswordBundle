<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent CHALAMON <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace CoopTilleuls\ForgotPasswordBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class UnauthorizedFieldException extends HttpException implements JsonHttpExceptionInterface
{
    public function __construct($propertyName)
    {
        parent::__construct(400, \sprintf('The parameter "%s" is not authorized in your configuration.', $propertyName));
    }
}
