<?php

/*
 * This file is part of the ForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoopTilleuls\ForgotPasswordBundle\Event;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
class ForgotPasswordEvent extends Event
{
    const CREATE_TOKEN = 'coop_tilleuls_forgot_password.create_token';
    const UPDATE_PASSWORD = 'coop_tilleuls_forgot_password.update_password';

    private $passwordToken;
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
     * @return null|string
     */
    public function getPassword()
    {
        return $this->password;
    }
}
