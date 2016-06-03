<?php

namespace CoopTilleuls\ForgotPasswordBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

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

    public function __construct()
    {
        $this->expiresAt = new \DateTime('1 day');
    }

    /**
     * @return int
     */
    abstract public function getId();

    /**
     * @return UserInterface
     */
    abstract public function getUser();

    /**
     * @param UserInterface $user
     */
    abstract public function setUser(UserInterface $user);

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
