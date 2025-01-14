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
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as ContractsEventDispatcherInterface;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
class ForgotPasswordManager
{
    public function __construct(private readonly PasswordTokenManager $passwordTokenManager, private readonly EventDispatcherInterface $dispatcher)
    {
    }

    public function resetPassword($propertyName, $value, ProviderInterface $provider): void
    {
        $context = [$propertyName => $value];

        $user = $provider->getManager()->findOneBy($provider->getUserClass(), $context);

        if (null === $user) {
            if ($this->dispatcher instanceof ContractsEventDispatcherInterface) {
                $this->dispatcher->dispatch(new UserNotFoundEvent($context));
            } else {
                $this->dispatcher->dispatch(UserNotFoundEvent::USER_NOT_FOUND, new UserNotFoundEvent($context));
            }

            return;
        }

        $token = $this->passwordTokenManager->findOneByUser($user, $provider);

        // A token already exists and has not expired
        if (null === $token || $token->isExpired()) {
            $expiredAt = new \DateTime($provider->getPasswordTokenExpiredIn());
            $expiredAt->setTime((int) $expiredAt->format('H'), (int) $expiredAt->format('i'), (int) $expiredAt->format('s'), 0);

            $token = $this->passwordTokenManager->createPasswordToken($user, $provider, $expiredAt);
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
    public function updatePassword(AbstractPasswordToken $passwordToken, $password, ProviderInterface $provider)
    {
        // Update user password
        if ($this->dispatcher instanceof ContractsEventDispatcherInterface) {
            $this->dispatcher->dispatch(new UpdatePasswordEvent($passwordToken, $password));
        } else {
            $this->dispatcher->dispatch(ForgotPasswordEvent::UPDATE_PASSWORD, new UpdatePasswordEvent($passwordToken, $password));
        }

        // Remove PasswordToken
        $provider->getManager()->remove($passwordToken);

        return true;
    }
}
