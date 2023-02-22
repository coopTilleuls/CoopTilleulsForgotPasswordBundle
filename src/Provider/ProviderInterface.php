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

/**
 * Configuration of ForgotPassword for each provider.
 */
interface ProviderInterface
{
    /**
     * User Class.
     */
    public function getUserClass(): string;

    /**
     * PasswordToken Class.
     */
    public function getPasswordTokenClass(): string;

    /**
     * PasswordToken expiration property.
     */
    public function getPasswordTokenExpiredIn(): string;

    /**
     * PasswordToken user field property.
     */
    public function getPasswordTokenUserField(): string;

    /**
     * PasswordToken serialization groups.
     */
    public function getPasswordTokenSerializationGroups(): array;

    /**
     * User email property.
     */
    public function getUserEmailField(): string;

    /**
     * User password property.
     */
    public function getUserPasswordField(): string;

    /**
     * User password/email property authorized.
     */
    public function getUserAuthorizedFields(): array;

    /**
     * If provider is Default no need to mention it in queries.
     */
    public function isDefault(): bool;

    public function getName(): string;
}
