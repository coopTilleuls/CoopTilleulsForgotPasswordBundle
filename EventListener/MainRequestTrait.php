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

namespace CoopTilleuls\ForgotPasswordBundle\EventListener;

use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * @author Jon Gotlin <jon@jon.se>
 */
trait MainRequestTrait
{
    private function isMainRequest(KernelEvent $event): bool
    {
        return method_exists($event, 'isMainRequest') ? $event->isMainRequest() : $event->isMasterRequest();
    }
}
