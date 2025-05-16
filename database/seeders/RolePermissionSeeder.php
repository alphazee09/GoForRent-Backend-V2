<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()["cache"]->forget("spatie.permission.cache");

        // Define Permissions
        $permissions = [
            // User Management
            "manage_users", "view_users", "create_users", "edit_users", "delete_users",
            // Role & Permission Management
            "manage_roles", "view_roles", "create_roles", "edit_roles", "delete_roles",
            "manage_permissions", "view_permissions", "assign_permissions",
            // Equipment Management
            "manage_equipment_categories", "view_equipment_categories", "create_equipment_categories", "edit_equipment_categories", "delete_equipment_categories",
            "manage_equipment", "view_equipment", "create_equipment", "edit_equipment", "delete_equipment", "list_own_equipment", "edit_own_equipment", "delete_own_equipment",
            // Rental Management
            "manage_rentals", "view_rentals", "create_rentals", "edit_rentals", "cancel_rentals", "view_own_rentals", "create_own_rentals",
            // Contract Management
            "manage_contracts", "view_contracts", "sign_contracts",
            // Payment Management
            "manage_payments", "view_payments", "process_payments",
            // Review & Rating Management
            "manage_reviews", "view_reviews", "approve_reviews", "delete_reviews", "create_reviews",
            // Damage Report Management
            "manage_damage_reports", "view_damage_reports", "create_damage_reports", "update_damage_reports",
            // Reward Points Management
            "manage_reward_points", "view_reward_points_history", "add_reward_points", "deduct_reward_points",
            // Banner Management
            "manage_banners", "view_banners", "create_banners", "edit_banners", "delete_banners",
            // Template Management (Email & Push)
            "manage_email_templates", "view_email_templates", "create_email_templates", "edit_email_templates", "delete_email_templates",
            "manage_push_notification_templates", "view_push_notification_templates", "create_push_notification_templates", "edit_push_notification_templates", "delete_push_notification_templates",
            // Global Settings Management
            "manage_global_settings", "view_global_settings", "edit_global_settings",
            // Admin Dashboard Access
            "access_admin_dashboard",
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(["name" => $permission, "guard_name" => "api"]);
        }

        // Define Roles
        $adminRole = Role::firstOrCreate(["name" => "Admin", "guard_name" => "api"]);
        $userRole = Role::firstOrCreate(["name" => "User", "guard_name" => "api"]); // Standard mobile app user
        $ownerRole = Role::firstOrCreate(["name" => "Owner", "guard_name" => "api"]); // Equipment owner

        // Assign Permissions to Admin
        $adminRole->givePermissionTo(Permission::all());

        // Assign Permissions to User (Mobile App User)
        $userPermissions = [
            "view_equipment_categories", "view_equipment",
            "view_own_rentals", "create_own_rentals", "cancel_rentals", // Assuming users can cancel their own rentals under certain conditions
            "sign_contracts", // For their own rentals
            "view_payments", // For their own rentals
            "create_reviews", // For equipment they rented
            "create_damage_reports", // For equipment they rented
            "view_reward_points_history", // For their own points
        ];
        $userRole->givePermissionTo($userPermissions);

        // Assign Permissions to Owner (Equipment Owner)
        $ownerPermissions = [
            "list_own_equipment", "create_equipment", "edit_own_equipment", "delete_own_equipment",
            "view_rentals", // Related to their equipment
            "view_contracts", // Related to their equipment
            "view_payments", // Related to their equipment rentals
            "view_damage_reports", // Related to their equipment
            "view_reviews", // For their equipment
        ];
        $ownerRole->givePermissionTo($ownerPermissions);
        // Owners are also users, so they should inherit user permissions or have them explicitly assigned if needed for app interaction
        // For simplicity here, we keep them distinct. In a real app, an Owner might also have the User role or a combined role.

        // Create a default Admin User if it doesn't exist
        $adminUser = User::firstOrCreate(
            ["email" => "admin@go4rent.com"],
            [
                "full_name" => "Admin User",
                "phone_number" => "0000000000",
                "password" => Hash::make("password"), // Change this in production!
                "email_verified_at" => now(),
                "phone_verified_at" => now(),
            ]
        );
        $adminUser->assignRole($adminRole);

        // Create a default regular User if it doesn't exist
        $regularUser = User::firstOrCreate(
            ["email" => "user@go4rent.com"],
            [
                "full_name" => "Regular User",
                "phone_number" => "1111111111",
                "password" => Hash::make("password"), // Change this in production!
                "email_verified_at" => now(),
                "phone_verified_at" => now(),
            ]
        );
        $regularUser->assignRole($userRole);
        
        // Create a default Owner User if it doesn't exist
        $ownerUser = User::firstOrCreate(
            ["email" => "owner@go4rent.com"],
            [
                "full_name" => "Owner User",
                "phone_number" => "2222222222",
                "password" => Hash::make("password"), // Change this in production!
                "email_verified_at" => now(),
                "phone_verified_at" => now(),
            ]
        );
        $ownerUser->assignRole($ownerRole);

    }
}

