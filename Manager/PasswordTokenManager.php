<?php

namespace CoopTilleuls\ForgotPasswordBundle\Manager;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\ManagerInterface;
use RandomLib\Factory;
use SecurityLib\Strength;

class PasswordTokenManager
{
    private $manager;
    private $passwordTokenClass;
    private $defaultExpiresIn;

    /**
     * @param ManagerInterface $manager
     * @param string           $passwordTokenClass
     * @param string           $defaultExpiresIn
     */
    public function __construct(ManagerInterface $manager, $passwordTokenClass, $defaultExpiresIn)
    {
        $this->manager = $manager;
        $this->passwordTokenClass = $passwordTokenClass;
        $this->defaultExpiresIn = $defaultExpiresIn;
    }

    /**
     * @param mixed          $user
     * @param \DateTime|null $expiresAt
     *
     * @return AbstractPasswordToken
     */
    public function createPasswordToken($user, \DateTime $expiresAt = null)
    {
        /** @var AbstractPasswordToken $passwordToken */
        $passwordToken = new $this->passwordTokenClass();

        $factory = new Factory();
        $generator = $factory->getGenerator(new Strength(Strength::MEDIUM));

        $passwordToken->setToken($generator->generateString(50, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'));
        $passwordToken->setUser($user);
        $passwordToken->setExpiresAt($expiresAt ?: new \DateTime($this->defaultExpiresIn));

        $this->manager->persist($passwordToken);

        return $passwordToken;
    }

    /**
     * @param string $token
     *
     * @return AbstractPasswordToken
     */
    public function findOneByToken($token)
    {
        return $this->manager->findOneBy($this->passwordTokenClass, ['token' => $token]);
    }
}
