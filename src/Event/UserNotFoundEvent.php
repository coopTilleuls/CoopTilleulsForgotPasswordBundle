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

namespace CoopTilleuls\ForgotPasswordBundle\Event;

final class UserNotFoundEvent extends PolyfillEvent
{
    public const USER_NOT_FOUND = 'coop_tilleuls_forgot_password.user_not_found';

    private array $context;

    public function __construct(array $context = [])
    {
        $this->context = $context;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
