<?php

declare(strict_types=1);

namespace Rimba\Ldap\Authenticators;

use Rimba\Ldap\Actions\AuthenticateLdapUser;
use Rimba\Ldap\Models\AdUser;
use Rimba\Ldap\Services\LdapDiscoveryService;
use Rimba\Who\Contracts\IdentityAuthenticatorContract;
use Rimba\Who\Enums\AuthenticationStatus;
use Rimba\Who\Support\AuthenticationResult;
use Rimba\Who\Support\ExternalIdentity;

final readonly class LdapAuthenticator implements IdentityAuthenticatorContract
{
    public function __construct(
        private LdapDiscoveryService $ldapDiscoveryService,
        private AuthenticateLdapUser $authenticateLdapUser,
    ) {}

    public function name(): string
    {
        return 'ldap';
    }

    public function authenticateExisting(
        string $identifier,
        string $password,
    ): AuthenticationResult {
        $ldapUser = $this->ldapDiscoveryService->find($identifier);

        if (! $ldapUser) {
            return new AuthenticationResult(
                status: AuthenticationStatus::Failed,
                provider: $this->name(),
                reason: 'directory_identity_missing',
            );
        }

        return $this->authenticateLdapUser->handle(
            ldapUser: $ldapUser,
            submittedIdentifier: $identifier,
            password: $password,
        );
    }

    public function authenticateExternal(
        ExternalIdentity $identity,
        string $password,
    ): AuthenticationResult {
        if (
            $identity->provider !== $this->name()
            || ! $identity->subject instanceof AdUser
        ) {
            return new AuthenticationResult(
                status: AuthenticationStatus::Failed,
                provider: $this->name(),
                reason: 'unsupported_external_identity',
            );
        }

        return $this->authenticateLdapUser->handle(
            ldapUser: $identity->subject,
            submittedIdentifier: $identity->canonicalIdentifier,
            password: $password,
        );
    }
}
