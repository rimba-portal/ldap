<?php

declare(strict_types=1);

namespace Rimba\Ldap\Exceptions;

use RuntimeException;

final class LdapIdentityConflict extends RuntimeException
{
    public static function forEmail(string $email): self
    {
        return new self(
            "The local user for [{$email}] is already linked to another authentication provider.",
        );
    }
}
