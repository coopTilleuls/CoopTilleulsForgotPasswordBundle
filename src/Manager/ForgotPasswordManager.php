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

namespace CoopTilleuls\ForgotPasswordBundle\Manager;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Event\CreateTokenEvent;
use CoopTilleuls\ForgotPasswordBundle\Event\ForgotPasswordEvent;
use CoopTilleuls\ForgotPasswordBundle\Event\UpdatePasswordEvent;
use CoopTilleuls\ForgotPasswordBundle\Event\UserNotFoundEvent;
use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\ManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as ContractsEventDispatcherInterface;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
class ForgotPasswordManager
{
    private $manager;
    private $passwordTokenManager;
    private $dispatcher;
    private $userClass;

    /**
     * @param string $userClass
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

    public function resetPassword($propertyName, $value): void
    {
        $context = [$propertyName => $value];

        $user = $this->manager->findOneBy($this->userClass, $context);
        if (null === $user) {
            if ($this->dispatcher instanceof ContractsEventDispatcherInterface) {
                $this->dispatcher->dispatch(new UserNotFoundEvent($context));
            } else {
                $this->dispatcher->dispatch(UserNotFoundEvent::USER_NOT_FOUND, new UserNotFoundEvent($context));
            }

            return;
        }

        $token = $this->passwordTokenManager->findOneByUser($user);

        // A token already exists and has not expired
        if (null === $token || $token->isExpired()) {
            $token = $this->passwordTokenManager->createPasswordToken($user);
        }

        // Generate password token
        if ($this->dispatcher instanceof ContractsEventDispatcherInterface) {
            $this->dispatcher->dispatch(new CreateTokenEvent($token));
        } else {
            $this->dispatcher->dispatch(ForgotPasswordEvent::CREATE_TOKEN, new CreateTokenEvent($token));
        }
    }

    /**
     * @param string $password
     *
     * @return bool
     */
    public function updatePassword(AbstractPasswordToken $passwordToken, $password)
    {
        // Update user password
        if ($this->dispatcher instanceof ContractsEventDispatcherInterface) {
            $this->dispatcher->dispatch(new UpdatePasswordEvent($passwordToken, $password));
        } else {
            $this->dispatcher->dispatch(ForgotPasswordEvent::UPDATE_PASSWORD, new UpdatePasswordEvent($passwordToken, $password));
        }

        // Remove PasswordToken
        $this->manager->remove($passwordToken);

        return true;
    }
}
