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
use CoopTilleuls\ForgotPasswordBundle\Provider\Provider;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderFactory;
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
    private $providerFactory;

    public function __construct(
        PasswordTokenManager $passwordTokenManager,
        EventDispatcherInterface $dispatcher,
        ManagerInterface $manager,
        ProviderFactory $providerFactory
    ) {
        $this->passwordTokenManager = $passwordTokenManager;
        $this->dispatcher = $dispatcher;
        $this->manager = $manager;
        $this->providerFactory = $providerFactory;
    }

    public function resetPassword($propertyName, $value, $providerName = null): void
    {
        /* @var Provider $provider */
        if (null !== $providerName) {
            $provider = $this->providerFactory->get($providerName);
        } else {
            $provider = $this->providerFactory->getDefault();
        }

        $context = [$propertyName => $value];

        $user = $this->manager->findOneBy($provider->getUserClass(), $context);

        if (null === $user) {
            if ($this->dispatcher instanceof ContractsEventDispatcherInterface) {
                $this->dispatcher->dispatch(new UserNotFoundEvent($context));
            } else {
                $this->dispatcher->dispatch(UserNotFoundEvent::USER_NOT_FOUND, new UserNotFoundEvent($context));
            }

            return;
        }

        $token = $this->passwordTokenManager->findOneByUser($provider->getPasswordTokenClass(), $user);

        // A token already exists and has not expired
        if (null === $token || $token->isExpired()) {
            $expiredAt = new \DateTime($provider->getPasswordTokenExpiredIn());
            $expiredAt->setTime((int) $expiredAt->format('H'), (int) $expiredAt->format('m'), (int) $expiredAt->format('s'), 0);

            $token = $this->passwordTokenManager->createPasswordToken($user, $expiredAt, $providerName);
        }

        // Generate password token
        if ($this->dispatcher instanceof ContractsEventDispatcherInterface) {
            $this->dispatcher->dispatch(new CreateTokenEvent($token));
        } else {
            $this->dispatcher->dispatch(ForgotPasswordEvent::CREATE_TOKEN, new CreateTokenEvent($token));
        }
    }

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
