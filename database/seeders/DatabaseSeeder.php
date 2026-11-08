<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed one dev account per role (password: "password") and the sample course catalog.
     */
    public function run(): void
    {
        $accounts = [
            ['email' => 'admin@reviewer.test', 'name' => 'Admin', 'role' => UserRole::Admin],
            ['email' => 'prof@reviewer.test', 'name' => 'Sample Professor', 'role' => UserRole::Professor],
            ['email' => 'student@reviewer.test', 'name' => 'Sample Student', 'role' => UserRole::Student],
        ];

        foreach ($accounts as $account) {
            User::firstOrCreate(
                ['email' => $account['email']],
                ['name' => $account['name'], 'password' => 'password'],
            )->forceFill(['role' => $account['role']])->save();
        }

        $this->call(CatalogSeeder::class);
    }
}
