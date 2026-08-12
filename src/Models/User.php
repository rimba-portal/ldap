<?php

declare(strict_types=1);

namespace Rimba\Ldap\Models;

use LdapRecord\Models\Model;

class User extends Model
{
    public static array $objectClasses = [
        'top',
        'person',
        'organizationalperson',
        'user',
    ];
}
