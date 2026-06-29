<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class AdminSystemSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'adminsistem@bpsbabel.go.id'],
            [
                'tenant_id' => null,
                'name' => 'Administrator Sistem',
                'login_id' => 'admin.sistem',
                'phone' => '081234567890',
                'role' => 'admin_sistem',
                'password' => Hash::make('Admin123'),
                'password_text' => Crypt::encryptString('Admin123'),
                'is_active' => true,
            ]
        );
    }
}