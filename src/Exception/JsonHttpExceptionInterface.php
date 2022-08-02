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

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
interface JsonHttpExceptionInterface extends HttpExceptionInterface
{
}
