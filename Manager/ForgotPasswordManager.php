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
    private $userEmailField;

    /**
     * @param PasswordTokenManager     $passwordTokenManager
     * @param EventDispatcherInterface $dispatcher
     * @param ManagerInterface         $manager
     * @param string                   $userClass
     * @param string                   $userEmailField
     */
    public function __construct(
        PasswordTokenManager $passwordTokenManager,
        EventDispatcherInterface $dispatcher,
        ManagerInterface $manager,
        $userClass,
        $userEmailField
    ) {
        $this->passwordTokenManager = $passwordTokenManager;
        $this->dispatcher = $dispatcher;
        $this->manager = $manager;
        $this->userClass = $userClass;
        $this->userEmailField = $userEmailField;
    }

    /**
     * @param string $username
     */
    public function resetPassword($username)
    {
        $user = $this->manager->findOneBy($this->userClass, [$this->userEmailField => $username]);
        if (null === $user) {
            return;
        }

        $token = $this->passwordTokenManager->findOneByUser($user);

        // A token already exists and has not expired
        if (null === $token || $token->isExpired()) {
            $token = $this->passwordTokenManager->createPasswordToken($user);
        }

        // Generate password token
        $this->dispatcher->dispatch(
            ForgotPasswordEvent::CREATE_TOKEN,
            new ForgotPasswordEvent($token)
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
