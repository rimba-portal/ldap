<?php

declare(strict_types=1);

namespace Rimba\Ldap\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Rimba\Ldap\Exceptions\LdapIdentityConflict;
use Rimba\Ldap\Models\AdUser;
use Rimba\Ldap\Services\LdapDiscoveryService;
use Rimba\Who\Actions\LinkStaffToUser;
use Rimba\Who\Models\UserAuth;
use RuntimeException;

final readonly class CreateLdapUser
{
    public function __construct(
        private LdapDiscoveryService $ldapDiscoveryService,
        private LinkStaffToUser $linkStaffToUser,
    ) {}

    public function handle(
        AdUser $ldapUser,
        string $submittedIdentifier,
    ): Authenticatable {
        $canonicalIdentifier = $this->ldapDiscoveryService
            ->canonicalIdentifier($ldapUser)
            ?? trim($submittedIdentifier);

        $email = $this->ldapDiscoveryService->email($ldapUser);

        if (blank($email)) {
            throw new RuntimeException(
                'The LDAP identity does not contain an email address.',
            );
        }

        $name = $this->ldapDiscoveryService->displayName($ldapUser)
            ?? $canonicalIdentifier;

        $staffNumber = $this->ldapDiscoveryService
            ->staffNumber($ldapUser);

        /** @var class-string<EloquentModel&Authenticatable> $userModel */
        $userModel = (string) config(
            'bites_auth.local_user_model',
            config('auth.providers.users.model'),
        );

        return DB::transaction(function () use (
            $userModel,
            $canonicalIdentifier,
            $email,
            $name,
            $staffNumber,
        ): Authenticatable {
            $userAuth = UserAuth::query()
                ->with('user')
                ->where('auth_provider', 'ldap')
                ->where('auth_identifier', $canonicalIdentifier)
                ->first();

            if ($userAuth?->user) {
                $user = $userAuth->user;

                $user->forceFill([
                    'name' => $name,
                    'email' => $email,
                ])->save();

                $this->linkStaffToUser->handle(
                    $user,
                    $staffNumber,
                );

                return $user;
            }

            /** @var EloquentModel&Authenticatable|null $user */
            $user = $userModel::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                ->first();

            if ($user) {
                $existingAuth = UserAuth::query()
                    ->where('user_id', $user->getAuthIdentifier())
                    ->first();

                if ($existingAuth && $existingAuth->auth_provider !== 'ldap') {
                    throw LdapIdentityConflict::forEmail($email);
                }

                $user->forceFill([
                    'name' => $name,
                    'email' => $email,
                ])->save();
            } else {
                /** @var EloquentModel&Authenticatable $user */
                $user = $userModel::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(64)),
                ]);
            }

            UserAuth::query()->updateOrCreate(
                [
                    'user_id' => $user->getAuthIdentifier(),
                ],
                [
                    'auth_provider' => 'ldap',
                    'auth_identifier' => $canonicalIdentifier,
                ],
            );

            $this->linkStaffToUser->handle(
                $user,
                $staffNumber,
            );

            return $user;
        });
    }
}
