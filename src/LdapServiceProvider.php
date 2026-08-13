<?php

declare(strict_types=1);

namespace Rimba\Ldap;

use Rimba\Base\Services\BitesServiceProvider;
use Rimba\Ldap\Services\LdapAuthService;

class LdapServiceProvider extends BitesServiceProvider
{
    protected function bootPackage(): void
    {
        //
    }

    protected function registerPackage(): void
    {
        $this->app->singleton(
            'bites_auth.auth-provider.ldap',
            LdapAuthService::class
        );

    }
}
