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
use CoopTilleuls\ForgotPasswordBundle\Event\UpdatePasswordEvent;
use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\ManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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

    /**
     * @param $propertyName
     * @param $value
     */
    public function resetPassword($propertyName, $value): void
    {
        $user = $this->manager->findOneBy($this->userClass, [$propertyName => $value]);
        if (null === $user) {
            return;
        }

        $token = $this->passwordTokenManager->findOneByUser($user);

        // A token already exists and has not expired
        if (null === $token || $token->isExpired()) {
            $token = $this->passwordTokenManager->createPasswordToken($user);
        }

        // Generate password token
        $this->dispatcher->dispatch(new CreateTokenEvent($token));
    }

    /**
     * @param string $password
     *
     * @return bool
     */
    public function updatePassword(AbstractPasswordToken $passwordToken, $password)
    {
        // Update user password
        $this->dispatcher->dispatch(new UpdatePasswordEvent($passwordToken, $password));

        // Remove PasswordToken
        $this->manager->remove($passwordToken);

        return true;
    }
}
