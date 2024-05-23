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

use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\ManagerInterface;

final class Provider implements ProviderInterface
{
    public function __construct(private readonly ManagerInterface $manager, private readonly string $name, private readonly string $passwordTokenClass, private readonly string $passwordTokenExpiredIn, private readonly string $passwordTokenUserField, private readonly string $userClass, private readonly array $passwordTokenSerializationGroups = [], private readonly string $userEmailField = 'email', private readonly string $userPasswordField = 'password', private readonly array $userAuthorizedFields = [], private readonly bool $isDefault = false)
    {
    }

    public function getManager(): ManagerInterface
    {
        return $this->manager;
    }

    public function getUserClass(): string
    {
        return $this->userClass;
    }

    public function getPasswordTokenClass(): string
    {
        return $this->passwordTokenClass;
    }

    public function getPasswordTokenExpiredIn(): string
    {
        return $this->passwordTokenExpiredIn;
    }

    public function getPasswordTokenUserField(): string
    {
        return $this->passwordTokenUserField;
    }

    public function getPasswordTokenSerializationGroups(): array
    {
        return $this->passwordTokenSerializationGroups;
    }

    public function getUserEmailField(): string
    {
        return $this->userEmailField;
    }

    public function getUserPasswordField(): string
    {
        return $this->userPasswordField;
    }

    public function getUserAuthorizedFields(): array
    {
        return $this->userAuthorizedFields;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
