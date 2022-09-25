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

class ProviderFactory
{
    private array $providers;

    public function __construct(iterable $providers)
    {
        $this->providers = iterator_to_array($providers);
    }

    public function get(string $name): ProviderInterface
    {
        if (!isset($this->providers[$name])) {
            throw new UndefinedProviderException($name);
        }

        return $this->providers[$name];
    }

    public function getDefault(): ProviderInterface
    {
        foreach ($this->providers as $provider) {
            if (true === $provider->isDefault()) {
                return $provider;
            }
        }

        throw new UndefinedProviderException('default provider');
    }
}
