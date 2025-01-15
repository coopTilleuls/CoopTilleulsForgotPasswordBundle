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

final class UndefinedProviderException extends HttpException implements JsonHttpExceptionInterface
{
    public function __construct(string $message = 'This provider is not defined.')
    {
        trigger_deprecation('tilleuls/forgot-password-bundle', '1.5', 'Status code will change to "%s" in 2.0.', 422);
        parent::__construct(400, $message);
    }
}
