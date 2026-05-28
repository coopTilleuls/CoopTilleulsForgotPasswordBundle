<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent CHALAMON <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoopTilleuls\ForgotPasswordBundle\Provider;

interface ProviderChainInterface
{
    public function get(?string $name = null): ProviderInterface;

    /**
     * @return array<string, ProviderInterface>
     */
    public function all(): iterable;
}
