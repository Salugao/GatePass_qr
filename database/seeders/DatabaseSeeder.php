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
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        foreach ([
            ['name' => 'System Administrator', 'email' => 'admin@gatepassqr.local', 'role' => 'administrator'],
            ['name' => 'Campus Security', 'email' => 'security@gatepassqr.local', 'role' => 'security'],
            ['name' => 'Laboratory Personnel', 'email' => 'laboratory@gatepassqr.local', 'role' => 'laboratory'],
            ['name' => 'Office Personnel', 'email' => 'office@gatepassqr.local', 'role' => 'office'],
        ] as $account) {
            User::updateOrCreate(['email' => $account['email']], $account + [
                'password' => Hash::make('GatePass!2026'),
                'must_change_password' => true,
                'is_active' => true,
            ]);
        }
    }
}
