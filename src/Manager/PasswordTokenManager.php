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
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderFactory;
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
        ProviderFactory $providerFactory
    ) {
        $this->manager = $manager;
        $this->providerFactory = $providerFactory;
    }

    /**
     * @param mixed|null $providerName
     *
     * @return AbstractPasswordToken
     */
    public function createPasswordToken($user, \DateTime $expiresAt = null, $providerName = null)
    {
        /* @var Provider $provider */
        if (null !== $providerName) {
            $provider = $this->providerFactory->get($providerName);
        } else {
            $provider = $this->providerFactory->getDefault();
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
     * @param string $token
     *
     * @return AbstractPasswordToken
     */
    public function findOneByToken($passwordTokenClass, $token)
    {
        return $this->manager->findOneBy($passwordTokenClass, ['token' => $token]);
    }

    /**
     * @return AbstractPasswordToken
     */
    public function findOneByUser($passwordTokenClass, $user)
    {
        return $this->manager->findOneBy($passwordTokenClass, ['user' => $user]);
    }
}
