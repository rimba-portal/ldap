<?php

declare(strict_types=1);

namespace Rimba\Ldap\Models;

use LdapRecord\Models\Model;

final class AdUser extends Model
{
    public static array $objectClasses = [
        'top',
        'person',
        'organizationalperson',
        'user',
    ];
}
