<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $ownerName = env('DASHBOARD_OWNER_NAME', 'Owner');
        $ownerEmail = env('DASHBOARD_OWNER_EMAIL', 'owner@local.test');
        $ownerPassword = env('DASHBOARD_PASSWORD', 'change-me');

        User::query()->updateOrCreate(
            ['email' => $ownerEmail],
            [
                'name' => $ownerName,
                'password' => Hash::make($ownerPassword),
            ]
        );
    }
}
