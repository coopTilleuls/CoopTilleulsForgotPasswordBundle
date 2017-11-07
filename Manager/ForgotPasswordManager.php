<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoopTilleuls\ForgotPasswordBundle\Manager;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Event\ForgotPasswordEvent;
use CoopTilleuls\ForgotPasswordBundle\Exception\UnexpiredTokenHttpException;
use CoopTilleuls\ForgotPasswordBundle\Exception\UserNotFoundHttpException;
use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\ManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
class ForgotPasswordManager
{
    private $manager;
    private $passwordTokenManager;
    private $dispatcher;
    private $userClass;

    /**
     * @param PasswordTokenManager     $passwordTokenManager
     * @param EventDispatcherInterface $dispatcher
     * @param ManagerInterface         $manager
     * @param string                   $userClass
     */
    public function __construct(
        PasswordTokenManager $passwordTokenManager,
        EventDispatcherInterface $dispatcher,
        ManagerInterface $manager,
        $userClass
    ) {
        $this->passwordTokenManager = $passwordTokenManager;
        $this->dispatcher = $dispatcher;
        $this->manager = $manager;
        $this->userClass = $userClass;
    }

    /**
     * @param $propertyName
     * @param $value
     */
    public function resetPassword($propertyName, $value)
    {
        $user = $this->manager->findOneBy($this->userClass, [$propertyName => $value]);
        if (null === $user) {
            throw new UserNotFoundHttpException($propertyName, $value);
        }

        $token = $this->passwordTokenManager->findOneByUser($user);
        // A token already exists and has not expired
        if (null !== $token && !$token->isExpired()) {
            throw new UnexpiredTokenHttpException();
        }

        // Generate password token
        $this->dispatcher->dispatch(
            ForgotPasswordEvent::CREATE_TOKEN,
            new ForgotPasswordEvent($this->passwordTokenManager->createPasswordToken($user))
        );
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
        $this->manager->remove($passwordToken);

        return true;
    }
}
