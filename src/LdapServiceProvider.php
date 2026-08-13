<?php

declare(strict_types=1);

namespace Rimba\Ldap;

use Rimba\Base\Services\BitesServiceProvider;
use Rimba\Ldap\Authenticators\LdapAuthenticator;
use Rimba\Ldap\Resolvers\LdapIdentityResolver;

class LdapServiceProvider extends BitesServiceProvider
{
    protected function bootPackage(): void
    {
        //
    }

    protected function registerPackage(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bites_auth.php', 'bites_auth');
        $this->app->singleton(LdapIdentityResolver::class);
        $this->app->singleton(LdapAuthenticator::class);
        $this->app->tag([LdapIdentityResolver::class], 'bites_auth.external-resolver');
        $this->app->tag([LdapAuthenticator::class], 'bites_auth.authenticator');

    }
}
