<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace CoopTilleuls\ForgotPasswordBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\Event as ContractsEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

if (is_subclass_of(EventDispatcher::class, EventDispatcherInterface::class)) {
    // Symfony 4.4 and upper
    abstract class PolyfillEvent extends ContractsEvent
    {
    }
} else {
    // Symfony 4.3 and inferior
    abstract class PolyfillEvent extends Event
    {
    }
}
