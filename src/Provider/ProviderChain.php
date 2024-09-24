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

namespace CoopTilleuls\ForgotPasswordBundle\Provider;

use CoopTilleuls\ForgotPasswordBundle\Exception\UndefinedProviderException;

final class ProviderChain implements ProviderChainInterface
{
    /**
     * @var array<string, ProviderInterface>
     */
    private array $providers;

    public function __construct(iterable $providers)
    {
        $this->providers = iterator_to_array($providers);
    }

    /**
     * Returns a provider by its name, without name the default provider is returned.
     */
    public function get(?string $name = null): ProviderInterface
    {
        if (null === $name) {
            return $this->getDefault();
        }

        if (!isset($this->providers[$name])) {
            throw new UndefinedProviderException(\sprintf('The provider "%s" is not defined.', $name));
        }

        return $this->providers[$name];
    }

    /**
     * Returns all providers indexed by name.
     *
     * @return array<string, ProviderInterface>
     */
    public function all(): iterable
    {
        return $this->providers;
    }

    private function getDefault(): ProviderInterface
    {
        foreach ($this->providers as $provider) {
            if (true === $provider->isDefault()) {
                return $provider;
            }
        }

        throw new UndefinedProviderException();
    }
}
