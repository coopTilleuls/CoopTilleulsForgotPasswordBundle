<?php

namespace CoopTilleuls\ForgotPasswordBundle\Entity;

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

    /**
     * @return int
     */
    abstract public function getId();

    /**
     * @return mixed
     */
    abstract public function getUser();

    /**
     * @param mixed $user
     */
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
    public function setToken($token)
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

    /**
     * @param \DateTime $expiresAt
     */
    public function setExpiresAt(\DateTime $expiresAt)
    {
        $this->expiresAt = $expiresAt;
    }
}
