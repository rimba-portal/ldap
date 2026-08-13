<?php

declare(strict_types=1);

namespace Rimba\Ldap\Resolvers;

use Rimba\Ldap\Services\LdapDiscoveryService;
use Rimba\Who\Contracts\ExternalIdentityResolverContract;
use Rimba\Who\Support\ExternalIdentity;

final readonly class LdapIdentityResolver implements ExternalIdentityResolverContract
{
    public function __construct(
        private LdapDiscoveryService $ldapDiscoveryService,
    ) {}

    public function name(): string
    {
        return 'ldap';
    }

    public function find(string $identifier): ?ExternalIdentity
    {
        $ldapUser = $this->ldapDiscoveryService->find($identifier);

        if (! $ldapUser) {
            return null;
        }

        $canonicalIdentifier = $this->ldapDiscoveryService
            ->canonicalIdentifier($ldapUser)
            ?? trim($identifier);

        return new ExternalIdentity(
            provider: $this->name(),
            canonicalIdentifier: $canonicalIdentifier,
            subject: $ldapUser,
        );
    }
}
