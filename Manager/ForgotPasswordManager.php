<?php

namespace CoopTilleuls\ForgotPasswordBundle\Manager;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Event\ForgotPasswordEvent;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ForgotPasswordManager
{
    /**
     * @var ObjectManager
     */
    private $entityManager;
    private $passwordTokenManager;
    private $dispatcher;
    private $userClass;
    private $userFieldName;

    /**
     * @param PasswordTokenManager     $passwordTokenManager
     * @param EventDispatcherInterface $dispatcher
     * @param ManagerRegistry          $managerRegistry
     * @param string                   $userClass
     * @param string                   $userFieldName
     */
    public function __construct(PasswordTokenManager $passwordTokenManager, EventDispatcherInterface $dispatcher, ManagerRegistry $managerRegistry, $userClass, $userFieldName)
    {
        $this->passwordTokenManager = $passwordTokenManager;
        $this->dispatcher = $dispatcher;
        $this->entityManager = $managerRegistry->getManagerForClass($userClass);
        $this->userClass = $userClass;
        $this->userFieldName = $userFieldName;
    }

    /**
     * @param string $username
     *
     * @return bool
     */
    public function resetPassword($username)
    {
        /** @var UserInterface $user */
        $user = $this->entityManager->getRepository($this->userClass)->findOneBy([$this->userFieldName => $username]);
        if (null === $user) {
            return false;
        }

        // Generate password token
        $this->dispatcher->dispatch(
            ForgotPasswordEvent::CREATE_TOKEN,
            new ForgotPasswordEvent($this->passwordTokenManager->createPasswordToken($user))
        );

        return true;
    }

    /**
     * @param AbstractPasswordToken $passwordToken
     * @param string                $password
     *
     * @return bool
     */
    public function updatePassword(AbstractPasswordToken $passwordToken, $password)
    {
        // Update user password
        $this->dispatcher->dispatch(
            ForgotPasswordEvent::UPDATE_PASSWORD,
            new ForgotPasswordEvent($passwordToken, $password)
        );

        // Remove PasswordToken
        $this->entityManager->remove($passwordToken);
        $this->entityManager->flush();
    }
}
