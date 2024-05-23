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
use CoopTilleuls\ForgotPasswordBundle\Provider\Provider;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderChainInterface;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use RandomLib\Factory;
use SecurityLib\Strength;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
class PasswordTokenManager
{
    public function __construct(private readonly ProviderChainInterface $providerChain)
    {
    }

    /**
     * @return AbstractPasswordToken
     */
    public function createPasswordToken($user, ?\DateTime $expiresAt = null, ?ProviderInterface $provider = null)
    {
        /* @var Provider $provider */
        if (!$provider) {
            trigger_deprecation('tilleuls/forgot-password-bundle', '1.5', 'Parameter "%s" in method "%s" is recommended since 1.5 and will be mandatory in 2.0.', '$provider', __METHOD__);
            $provider = $this->providerChain->get();
        }

        if (!$expiresAt) {
            $expiresAt = new \DateTime($provider->getPasswordTokenExpiredIn());
            $expiresAt->setTime((int) $expiresAt->format('H'), (int) $expiresAt->format('i'), (int) $expiresAt->format('s'), 0);
        }

        $tokenClass = $provider->getPasswordTokenClass();

        /** @var AbstractPasswordToken $passwordToken */
        $passwordToken = new $tokenClass();

        if (version_compare(\PHP_VERSION, '7.0', '>')) {
            $passwordToken->setToken(bin2hex(random_bytes(25)));
        } else {
            $factory = new Factory();
            $generator = $factory->getGenerator(new Strength(Strength::MEDIUM));

            $passwordToken->setToken(
                $generator->generateString(50, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
            );
        }

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
    public function findOneByToken($token, ?ProviderInterface $provider = null)
    {
        /* @var null|Provider $provider */
        if (!$provider) {
            trigger_deprecation('tilleuls/forgot-password-bundle', '1.5', 'Parameter "%s" in method "%s" is recommended since 1.5 and will be mandatory in 2.0.', '$provider', __METHOD__);
            $provider = $this->providerChain->get();
        }

        return $provider->getManager()->findOneBy($provider->getPasswordTokenClass(), ['token' => $token]);
    }

    /**
     * @return AbstractPasswordToken
     */
    public function findOneByUser($user, ?ProviderInterface $provider = null)
    {
        /* @var null|Provider $provider */
        if (!$provider) {
            trigger_deprecation('tilleuls/forgot-password-bundle', '1.5', 'Parameter "%s" in method "%s" is recommended since 1.5 and will be mandatory in 2.0.', '$provider', __METHOD__);
            $provider = $this->providerChain->get();
        }

        return $provider->getManager()->findOneBy($provider->getPasswordTokenClass(), [$provider->getPasswordTokenUserField() => $user]);
    }
}
