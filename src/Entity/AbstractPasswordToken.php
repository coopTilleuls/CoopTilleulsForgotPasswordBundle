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

namespace CoopTilleuls\ForgotPasswordBundle\Entity;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
abstract class AbstractPasswordToken
{
    /**
     * @var string
     */
    protected $token;

    /**
     * @var \DateTime
     */
    protected $expiresAt;

    abstract public function getId();

    abstract public function getUser();

    abstract public function setUser($user);

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token): void
    {
        $this->token = $token;
    }

    /**
     * @return \DateTime
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTime $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return (new \DateTime()) > $this->expiresAt;
    }
}
