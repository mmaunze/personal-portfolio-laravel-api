<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Posts
            'view-posts',
            'create-posts',
            'edit-posts',
            'delete-posts',
            'publish-posts',
            
            // Projects
            'view-projects',
            'create-projects',
            'edit-projects',
            'delete-projects',
            'publish-projects',
            
            // Downloads
            'view-downloads',
            'create-downloads',
            'edit-downloads',
            'delete-downloads',
            'publish-downloads',
            
            // Contacts
            'view-contacts',
            'reply-contacts',
            'delete-contacts',
            'manage-contacts',
            
            // Users
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'manage-roles',
            
            // System
            'view-dashboard',
            'manage-settings',
            'view-analytics'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Admin role - all permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Editor role - content management
        $editorRole = Role::create(['name' => 'editor']);
        $editorRole->givePermissionTo([
            'view-posts', 'create-posts', 'edit-posts', 'publish-posts',
            'view-projects', 'create-projects', 'edit-projects', 'publish-projects',
            'view-downloads', 'create-downloads', 'edit-downloads', 'publish-downloads',
            'view-contacts', 'reply-contacts',
            'view-dashboard', 'view-analytics'
        ]);

        // Author role - limited content creation
        $authorRole = Role::create(['name' => 'author']);
        $authorRole->givePermissionTo([
            'view-posts', 'create-posts', 'edit-posts',
            'view-projects', 'create-projects', 'edit-projects',
            'view-downloads', 'create-downloads', 'edit-downloads',
            'view-dashboard'
        ]);

        // Viewer role - read only
        $viewerRole = Role::create(['name' => 'viewer']);
        $viewerRole->givePermissionTo([
            'view-posts',
            'view-projects',
            'view-downloads',
            'view-dashboard'
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}

