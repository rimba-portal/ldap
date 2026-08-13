<?php

declare(strict_types=1);

namespace Rimba\Ldap\Services;

use Rimba\Ldap\Models\AdUser;

final class LdapDiscoveryService
{
    public function find(string $identifier): ?AdUser
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return AdUser::query()
                ->where(
                    $this->attribute('email', 'mail'),
                    '=',
                    $identifier,
                )
                ->orWhere(
                    $this->attribute('principal_name', 'userprincipalname'),
                    '=',
                    $identifier,
                )
                ->first();
        }

        return AdUser::query()
            ->where(
                $this->attribute('identifier', 'samaccountname'),
                '=',
                $identifier,
            )
            ->first();
    }

    public function canonicalIdentifier(AdUser $user): ?string
    {
        return $this->firstAttribute(
            $user,
            $this->attribute('identifier', 'samaccountname'),
        );
    }

    public function email(AdUser $user): ?string
    {
        return $this->firstAttribute(
            $user,
            $this->attribute('email', 'mail'),
        );
    }

    public function displayName(AdUser $user): ?string
    {
        return $this->firstAttribute(
            $user,
            $this->attribute('display_name', 'displayname'),
        ) ?? $this->firstAttribute(
            $user,
            $this->attribute('common_name', 'cn'),
        );
    }

    public function staffNumber(AdUser $user): ?string
    {
        return $this->firstAttribute(
            $user,
            $this->attribute('staff_number', 'employeenumber'),
        ) ?? $this->canonicalIdentifier($user);
    }

    private function attribute(string $key, string $default): string
    {
        return (string) config("bites_auth.attributes.{$key}", $default);
    }

    private function firstAttribute(AdUser $user, string $attribute): ?string
    {
        $value = $user->getFirstAttribute($attribute);

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
