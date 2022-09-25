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

interface ProviderInterface
{
    public function getUserClass(): string;

    public function getPasswordTokenClass(): string;

    public function getPasswordTokenExpiredIn(): string;

    public function getPasswordTokenUserField(): string;

    public function getPasswordTokenSerializationGroups(): array;

    public function getUserEmailField(): string;

    public function getUserPasswordField(): string;

    public function getUserAuthorizedFields(): array;

    public function isDefault(): bool;
}
