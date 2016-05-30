<?php

namespace CoopTilleuls\ForgotPasswordBundle\Manager;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use RandomLib\Factory;
use SecurityLib\Strength;
use Symfony\Component\Security\Core\User\UserInterface;

class PasswordTokenManager
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    private $passwordTokenClass;

    /**
     * @param Registry $registry
     * @param string   $passwordTokenClass
     */
    public function __construct(Registry $registry, $passwordTokenClass)
    {
        $this->entityManager = $registry->getManager();
        $this->passwordTokenClass = $passwordTokenClass;
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

        $factory = new Factory();;
        $generator = $factory->getGenerator(new Strength(Strength::MEDIUM));

        $passwordToken->setToken($generator->generateString(50, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'));
        $passwordToken->setUser($user);
        $passwordToken->setExpiresAt($expiresAt instanceof \DateTime ? $expiresAt : new \DateTime($expiresAt));

        $this->entityManager->persist($passwordToken);

        if (true === $flush) {
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
