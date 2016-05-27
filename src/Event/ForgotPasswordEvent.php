<?php

namespace ForgotPasswordBundle\Event;

use ForgotPasswordBundle\Entity\AbstractPasswordToken;
use Symfony\Component\EventDispatcher\Event;

class ForgotPasswordEvent extends Event
{
    const CREATE_TOKEN = 'forgot_password.create_token';
    const UPDATE_PASSWORD = 'forgot_password.update_password';

    /**
     * @var AbstractPasswordToken
     */
    private $passwordToken;

    /**
     * @var string
     */
    private $password;

    /**
     * @param AbstractPasswordToken $passwordToken
     * @param string                $password
     */
    public function __construct(AbstractPasswordToken $passwordToken, $password = null)
    {
        $this->passwordToken = $passwordToken;
        $this->password = $password;
    }

    /**
     * @return AbstractPasswordToken
     */
    public function getPasswordToken()
    {
        return $this->passwordToken;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
}
