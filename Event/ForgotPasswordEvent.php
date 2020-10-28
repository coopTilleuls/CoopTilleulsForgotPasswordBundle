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

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * @deprecated Use CreateTokenEvent and UpdatePasswordEvent instead
 */
class ForgotPasswordEvent extends PolyfillEvent
{
    const CREATE_TOKEN = 'coop_tilleuls_forgot_password.create_token';
    const UPDATE_PASSWORD = 'coop_tilleuls_forgot_password.update_password';

    protected $passwordToken;
    protected $password;

    /**
     * @param string $password
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
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }
}
