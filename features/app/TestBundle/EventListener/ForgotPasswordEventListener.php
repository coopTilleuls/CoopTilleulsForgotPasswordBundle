<?php

namespace ForgotPasswordBundle\Tests\TestBundle\EventListener;

use ForgotPasswordBundle\Event\ForgotPasswordEvent;

class ForgotPasswordEventListener
{
    public function onCreateToken(ForgotPasswordEvent $event)
    {
    }

    public function onUpdatePassword(ForgotPasswordEvent $event)
    {
    }
}
