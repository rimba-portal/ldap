<?php

declare(strict_types=1);

namespace Rimba\Ldap\Actions;

use Rimba\Ldap\Models\AdUser;
use Rimba\Who\Enums\AuthenticationStatus;
use Rimba\Who\Support\AuthenticationResult;
use Throwable;

final readonly class AuthenticateLdapUser
{
    public function __construct(
        private CreateLdapUser $createLdapUser,
    ) {}

    public function handle(
        AdUser $ldapUser,
        string $submittedIdentifier,
        string $password,
    ): AuthenticationResult {
        if ($password === '') {
            return new AuthenticationResult(
                status: AuthenticationStatus::Failed,
                provider: 'ldap',
                reason: 'missing_credentials',
            );
        }

        try {
            $authenticated = $ldapUser
                ->getConnection()
                ->auth()
                ->attempt(
                    $ldapUser->getDn(),
                    $password,
                );

            if (! $authenticated) {
                return new AuthenticationResult(
                    status: AuthenticationStatus::Failed,
                    provider: 'ldap',
                    reason: 'invalid_credentials',
                );
            }

            $user = $this->createLdapUser->handle(
                ldapUser: $ldapUser,
                submittedIdentifier: $submittedIdentifier,
            );

            return new AuthenticationResult(
                status: AuthenticationStatus::Success,
                provider: 'ldap',
                user: $user,
            );
        } catch (Throwable $throwable) {
            report($throwable);

            return new AuthenticationResult(
                status: AuthenticationStatus::Failed,
                provider: 'ldap',
                reason: 'provider_error',
            );
        }
    }
}
