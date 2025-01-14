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
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use CoopTilleuls\ForgotPasswordBundle\TokenGenerator\TokenGeneratorInterface;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
class PasswordTokenManager
{
    public function __construct(private readonly TokenGeneratorInterface $tokenGenerator)
    {
    }

    /**
     * @return AbstractPasswordToken
     */
    public function createPasswordToken($user, ProviderInterface $provider, ?\DateTime $expiresAt = null)
    {
        if (!$expiresAt) {
            $expiresAt = new \DateTime($provider->getPasswordTokenExpiredIn());
            $expiresAt->setTime((int) $expiresAt->format('H'), (int) $expiresAt->format('i'), (int) $expiresAt->format('s'), 0);
        }

        $tokenClass = $provider->getPasswordTokenClass();

        /** @var AbstractPasswordToken $passwordToken */
        $passwordToken = new $tokenClass();
        $passwordToken->setToken($this->tokenGenerator->generate());
        $passwordToken->setUser($user);
        $passwordToken->setExpiresAt($expiresAt);
        $provider->getManager()->persist($passwordToken);

        return $passwordToken;
    }

    /**
     * @param string $token
     *
     * @return AbstractPasswordToken
     */
    public function findOneByToken($token, ProviderInterface $provider)
    {
        return $provider->getManager()->findOneBy($provider->getPasswordTokenClass(), ['token' => $token]);
    }

    /**
     * @return AbstractPasswordToken
     */
    public function findOneByUser($user, ProviderInterface $provider)
    {
        return $provider->getManager()->findOneBy($provider->getPasswordTokenClass(), [$provider->getPasswordTokenUserField() => $user]);
    }
}
