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

final class Provider implements ProviderInterface
{
    private string $passwordTokenClass;
    private string $passwordTokenExpiredIn;
    private string $passwordTokenUserField;
    private ?array $passwordTokenSerializationGroups;
    private string $userClass;
    private ?string $userEmailField;
    private ?string $userPasswordField;
    private ?array $userAuthorizedFields;
    private ?bool $isDefault;

    public function __construct(
        string $passwordTokenClass,
        string $passwordTokenExpiredIn,
        string $passwordTokenUserField,
        string $userClass,
        ?array $passwordTokenSerializationGroups = [],
        ?string $userEmailField = 'email',
        ?string $userPasswordField = 'password',
        ?array $userAuthorizedFields = [],
        ?bool $isDefault = false
    ) {
        $this->passwordTokenClass = $passwordTokenClass;
        $this->passwordTokenExpiredIn = $passwordTokenExpiredIn;
        $this->passwordTokenUserField = $passwordTokenUserField;
        $this->passwordTokenSerializationGroups = $passwordTokenSerializationGroups;
        $this->userClass = $userClass;
        $this->userEmailField = $userEmailField;
        $this->userPasswordField = $userPasswordField;
        $this->userAuthorizedFields = $userAuthorizedFields;
        $this->isDefault = $isDefault;
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
}
