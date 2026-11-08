<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('users:make-admin {email} {--name= : Name for a new account}')]
#[Description('Promote an account to Admin, creating it with a random password if it does not exist')]
class MakeAdmin extends Command
{
    public function handle(): int
    {
        $email = Str::lower((string) $this->argument('email'));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->components->error("\"{$email}\" is not a valid email address.");

            return self::FAILURE;
        }

        $user = User::firstWhere('email', $email);
        $password = null;

        if ($user === null) {
            $password = Str::password(16, symbols: false);
            $user = User::create([
                'name' => $this->option('name') ?: Str::before($email, '@'),
                'email' => $email,
                'password' => $password,
            ]);
        }

        $user->forceFill(['role' => UserRole::Admin])->save();

        $this->components->info("{$email} is now an Admin.");

        if ($password !== null) {
            $this->components->twoColumnDetail('Temporary password', $password);
            $this->components->warn('Sign in and change it from your profile page.');
        }

        return self::SUCCESS;
    }
}
