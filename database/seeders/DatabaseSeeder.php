<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $adminEmail = env('ADMIN_EMAIL');
        // $adminPassword = env('ADMIN_PASSWORD');

        // if (! $adminEmail || ! $adminPassword) {
        //     throw new \RuntimeException('ADMIN_EMAIL and ADMIN_PASSWORD must be set before seeding the admin account.');
        // }

        // $admin = User::withTrashed()->firstOrNew(['email' => $adminEmail]);
        // $admin->restore();
        // $admin->fill([
        //     'name' => env('ADMIN_NAME', 'Admin'),
        //     'password' => Hash::make($adminPassword),
        //     'account_status' => 'Active',
        //     'role_name' => 'Admin',
        // ]);
        // $admin->save();
        User::updateOrCreate(
            ['email' => 'deanna.montejo@lsu.edu.ph'],
            [
                'name' => 'Lead Broker',
                'password' => Hash::make('deannamontejo'),
                'account_status' => 'Active',
                'role_name' => 'Lead Broker', 
            ]
        );
    }
}
