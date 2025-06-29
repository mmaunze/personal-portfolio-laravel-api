<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Cesário Machava',
            'email' => 'admin@cesariomachava.com',
            'password' => Hash::make('admin123'),
            'bio' => 'Administrador do sistema',
            'is_active' => true,
            'email_verified_at' => now()
        ]);

        // Assign admin role
        $admin->assignRole('admin');

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@cesariomachava.com');
        $this->command->info('Password: admin123');

        // Create editor user
        $editor = User::create([
            'name' => 'Editor Teste',
            'email' => 'editor@cesariomachava.com',
            'password' => Hash::make('editor123'),
            'bio' => 'Editor de conteúdo',
            'is_active' => true,
            'email_verified_at' => now()
        ]);

        // Assign editor role
        $editor->assignRole('editor');

        $this->command->info('Editor user created successfully!');
        $this->command->info('Email: editor@cesariomachava.com');
        $this->command->info('Password: editor123');
    }
}

