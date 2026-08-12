<?php

declare(strict_types=1);

namespace Rimba\Ldap\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Rimb\Rimba\Ldap\Models\User as LdapUser;
use Rimba\Who\Contracts\AuthProviderContract;
use Rimba\Who\Enums\AuthenticationStatus;
use Rimba\Who\Support\AuthenticationResult;
use Throwable;

final class LdapAuthService implements AuthProviderContract
{
    public function name(): string
    {
        return 'ldap';
    }

    public function authenticate(
        string $identifier,
        string $password,
    ): AuthenticationResult {

        try {

            $ldapUser = LdapUser::query()
                ->where('samaccountname', '=', $identifier)
                ->orWhere('mail', '=', $identifier)
                ->orWhere('userprincipalname', '=', $identifier)
                ->first();

            if (! $ldapUser) {
                return new AuthenticationResult(
                    AuthenticationStatus::NotFound,
                    $this->name(),
                );
            }

            $authenticated = $ldapUser
                ->getConnection()
                ->auth()
                ->attempt(
                    $ldapUser->getDn(),
                    $password
                );

            if (! $authenticated) {
                return new AuthenticationResult(
                    AuthenticationStatus::Failed,
                    $this->name(),
                    reason: 'invalid_credentials'
                );
            }

            $user = $this->resolveLocalUser(
                $ldapUser,
                $identifier
            );

            return new AuthenticationResult(
                AuthenticationStatus::Success,
                $this->name(),
                $user
            );

        } catch (Throwable $throwable) {

            report($throwable);

            return new AuthenticationResult(
                AuthenticationStatus::Failed,
                $this->name(),
                reason: 'provider_error'
            );
        }
    }

    private function resolveLocalUser(
        LdapUser $ldapUser,
        string $identifier
    ): Authenticatable {

        $model = config('auth.providers.users.model');

        $username =
            $ldapUser->getFirstAttribute('samaccountname')
            ?? $identifier;

        $email =
            $ldapUser->getFirstAttribute('mail');

        $name =
            $ldapUser->getFirstAttribute('displayname')
            ?? $ldapUser->getFirstAttribute('cn')
            ?? $username;

        return $model::query()->updateOrCreate(
            [
                'username' => $username,
            ],
            [
                'email' => $email,
                'name' => $name,
            ]
        );
    }
}
