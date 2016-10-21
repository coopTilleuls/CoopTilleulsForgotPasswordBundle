<?php

namespace CoopTilleuls\ForgotPasswordBundle\EventListener;

use CoopTilleuls\ForgotPasswordBundle\Exception\JsonHttpExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

final class ExceptionEventListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        if (!$event->isMasterRequest() || !$exception instanceof JsonHttpExceptionInterface) {
            return;
        }

        $event->setResponse(new JsonResponse(['message' => $exception->getMessage()], $exception->getStatusCode()));
    }
}
