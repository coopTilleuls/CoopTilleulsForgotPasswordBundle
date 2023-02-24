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
use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\ManagerInterface;
use CoopTilleuls\ForgotPasswordBundle\Provider\Provider;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderFactoryInterface;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use RandomLib\Factory;
use SecurityLib\Strength;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
class PasswordTokenManager
{
    private $manager;
    private $providerFactory;

    public function __construct(
        ManagerInterface $manager,
        ProviderFactoryInterface $providerFactory
    ) {
        $this->manager = $manager;
        $this->providerFactory = $providerFactory;
    }

    /**
     * @return AbstractPasswordToken
     */
    public function createPasswordToken($user, \DateTime $expiresAt = null, ?ProviderInterface $provider = null)
    {
        /* @var Provider $provider */
        if (!$provider) {
            trigger_deprecation('tilleuls/forgot-password-bundle', '1.5', 'Parameter "$provider" is recommended since 1.5 and will be mandatory in 2.0.');
            $provider = $this->providerFactory->get();
        }

        if (!$expiresAt) {
            $expiredAt = new \DateTime($provider->getPasswordTokenExpiredIn());
            $expiredAt->setTime((int) $expiredAt->format('H'), (int) $expiredAt->format('m'), (int) $expiredAt->format('s'), 0);
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
        $this->manager->persist($passwordToken);

        return $passwordToken;
    }

    /**
     * @param string     $token
     * @param mixed|null $passwordTokenClass
     *
     * @return AbstractPasswordToken
     */
    public function findOneByToken($token, $passwordTokenClass = null)
    {
        if (!$passwordTokenClass) {
            trigger_deprecation('tilleuls/forgot-password-bundle', '1.5', 'Parameter "$passwordTokenClass" is recommended since 1.5 and will be mandatory in 2.0.');
            $passwordTokenClass = $this->providerFactory->get()->getPasswordTokenClass();
        }

        return $this->manager->findOneBy($passwordTokenClass, ['token' => $token]);
    }

    /**
     * @param mixed|null $passwordTokenClass
     * @param mixed|null $passwordTokenUserField
     *
     * @return AbstractPasswordToken
     */
    public function findOneByUser($user, $passwordTokenClass = null, $passwordTokenUserField = null)
    {
        $provider = $this->providerFactory->get();

        if (!$passwordTokenClass) {
            trigger_deprecation('tilleuls/forgot-password-bundle', '1.5', 'Parameter "$passwordTokenClass" is recommended since 1.5 and will be mandatory in 2.0.');
            $passwordTokenClass = $provider->getPasswordTokenClass();
        }

        if (!$passwordTokenUserField) {
            trigger_deprecation('tilleuls/forgot-password-bundle', '1.5', 'Parameter "$passwordTokenUserField" is recommended since 1.5 and will be mandatory in 2.0.');
            $passwordTokenUserField = $provider->getPasswordTokenUserField();
        }

        return $this->manager->findOneBy($passwordTokenClass, [$passwordTokenUserField => $user]);
    }
}
