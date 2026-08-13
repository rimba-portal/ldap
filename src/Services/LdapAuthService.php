<?php

declare(strict_types=1);

namespace Rimba\Ldap\Services;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Rimba\Ldap\Models\User as AdUser;
use Rimba\People\Models\Staff;
use Rimba\Who\Contracts\AuthProviderContract;
use Rimba\Who\Enums\AuthenticationStatus;
use Rimba\Who\Models\UserAuth;
use Rimba\Who\Support\AuthenticationResult;
use Throwable;

final class LdapAuthService implements AuthProviderContract
{
    public function name(): string
    {
        return 'ldap';
    }

    public function authenticate(
        string $login,
        string $password,
    ): AuthenticationResult {
        try {
            $ldapUser = $this->findUser($login);

            if (! $ldapUser) {
                return new AuthenticationResult(
                    status: AuthenticationStatus::NotFound,
                    provider: $this->name(),
                    reason: 'user_not_found',
                );
            }

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
                    provider: $this->name(),
                    reason: 'invalid_credentials',
                );
            }

            $user = $this->resolveLocalUser(
                ldapUser: $ldapUser,
                login: $login,
            );

            return new AuthenticationResult(
                status: AuthenticationStatus::Success,
                provider: $this->name(),
                user: $user,
            );
        } catch (Throwable $throwable) {
            report($throwable);

            return new AuthenticationResult(
                status: AuthenticationStatus::Failed,
                provider: $this->name(),
                reason: 'provider_error',
            );
        }
    }

    private function findUser(string $login): ?AdUser
    {
        $attribute = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? 'mail'
            : 'samaccountname';

        return AdUser::query()
            ->where($attribute, '=', $login)
            ->first();
    }

    private function resolveLocalUser(
        AdUser $ldapUser,
        string $login,
    ): Authenticatable {
        /** @var class-string<Authenticatable> $model */
        $model = config(
            'auth.providers.users.model',
            User::class,
        );

        $username = $ldapUser->getFirstAttribute('samaccountname')
            ?: $login;

        $email = $ldapUser->getFirstAttribute('mail');

        $name = $ldapUser->getFirstAttribute('displayname')
            ?: $ldapUser->getFirstAttribute('cn')
            ?: $username;

        /*
         * Your AD account has samaccountname = staff number.
         * employeenumber may not exist, as seen for your own account.
         */
        $staffNumber = $ldapUser->getFirstAttribute('employeenumber')
            ?: $username;

        return DB::transaction(function () use (
            $model,
            $username,
            $email,
            $name,
            $staffNumber,
        ): Authenticatable {
            /*
             * First, find an existing LDAP identity.
             */
            $userAuth = UserAuth::query()
                ->with('user')
                ->where('auth_provider', $this->name())
                ->where('auth_identifier', $username)
                ->first();

            if ($userAuth?->user) {
                $user = $userAuth->user;

                $user->forceFill([
                    'name' => $name,
                    'email' => $email,
                ])->save();

                $userAuth->forceFill([
                    'username' => $username,
                ])->save();

                $this->linkStaff(
                    user: $user,
                    staffNumber: $staffNumber,
                );

                return $user;
            }

            /*
             * The local user may already exist through registration,
             * initial seeding, or another earlier process.
             */
            $user = $model::query()
                ->where('email', $email)
                ->first();

            if ($user) {
                $user->forceFill([
                    'name' => $name,
                    'email' => $email,
                ])->save();
            } else {
                /*
                 * LDAP validates the real password.
                 * This random local password is never shown or used.
                 */
                $user = $model::query()->create([
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
                    'username' => $username,
                    'auth_provider' => $this->name(),
                    'auth_identifier' => $username,
                ],
            );

            $this->linkStaff(
                user: $user,
                staffNumber: $staffNumber,
            );

            return $user;
        });
    }

    private function linkStaff(
        Authenticatable $user,
        ?string $staffNumber,
    ): void {
        if (blank($staffNumber)) {
            return;
        }

        Staff::query()
            ->where('staff_no', $staffNumber)
            ->update([
                'user_id' => $user->getAuthIdentifier(),
            ]);
    }
}
