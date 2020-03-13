<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoopTilleuls\ForgotPasswordBundle\EventListener;

use CoopTilleuls\ForgotPasswordBundle\Exception\JsonHttpExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class ExceptionEventListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        if (!$event->isMasterRequest() || !$exception instanceof JsonHttpExceptionInterface) {
            return;
        }

        $event->setResponse(new JsonResponse(['message' => $exception->getMessage()], $exception->getStatusCode()));
    }
}
