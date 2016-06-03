<?php

namespace CoopTilleuls\ForgotPasswordBundle\Manager;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use Doctrine\Common\Persistence\ManagerRegistry;
use RandomLib\Factory;
use SecurityLib\Strength;
use Symfony\Component\Security\Core\User\UserInterface;

class PasswordTokenManager
{
    private $entityManager;
    private $passwordTokenClass;
    private $defaultExpiresIn;

    /**
     * @param ManagerRegistry $registry
     * @param string          $passwordTokenClass
     * @param string          $defaultExpiresIn
     */
    public function __construct(ManagerRegistry $registry, $passwordTokenClass, $defaultExpiresIn)
    {
        $this->entityManager = $registry->getManagerForClass($passwordTokenClass);
        $this->passwordTokenClass = $passwordTokenClass;
        $this->defaultExpiresIn = $defaultExpiresIn;
    }

    /**
     * @param UserInterface  $user
     * @param \DateTime|null $expiresAt
     * @param bool           $flush
     *
     * @return AbstractPasswordToken
     */
    public function createPasswordToken(UserInterface $user, \DateTime $expiresAt = null, $flush = true)
    {
        /** @var AbstractPasswordToken $passwordToken */
        $passwordToken = new $this->passwordTokenClass();

        $factory = new Factory();
        $generator = $factory->getGenerator(new Strength(Strength::MEDIUM));

        $passwordToken->setToken($generator->generateString(50, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'));
        $passwordToken->setUser($user);
        $passwordToken->setExpiresAt($expiresAt ?: new \DateTime($this->defaultExpiresIn));

        $this->entityManager->persist($passwordToken);

        if ($flush) {
            $this->entityManager->flush($passwordToken);
        }

        return $passwordToken;
    }

    /**
     * @param string $token
     *
     * @return AbstractPasswordToken
     */
    public function findOneByToken($token)
    {
        return $this->entityManager->getRepository($this->passwordTokenClass)->findOneBy(['token' => $token]);
    }
}
