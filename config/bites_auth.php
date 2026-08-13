<?php

declare(strict_types=1);
use App\Models\User;
use Rimba\People\Models\Staff;

return [
    'connection' => env('RIMBA_LDAP_CONNECTION', 'default'),

    'attributes' => [
        'identifier' => 'samaccountname',
        'email' => 'mail',
        'principal_name' => 'userprincipalname',
        'display_name' => 'displayname',
        'common_name' => 'cn',
        'staff_number' => 'employeenumber',
    ],

    'local_user_model' => env(
        'RIMBA_LDAP_LOCAL_USER_MODEL',
        config('auth.providers.users.model', User::class),
    ),

    'staff_model' => env(
        'RIMBA_LDAP_STAFF_MODEL',
        Staff::class,
    ),

    'staff_number_column' => 'staff_no',
    'staff_user_column' => 'user_id',
];
