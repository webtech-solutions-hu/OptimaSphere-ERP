<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'System Administrator',
                'slug' => 'system-administrator',
                'description' => 'Full system access with administrative privileges. Can manage all aspects of the system.',
                'supervisor' => true,
            ],
            [
                'name' => 'Power User',
                'slug' => 'power-user',
                'description' => 'Advanced user with elevated permissions across multiple modules.',
            ],
            [
                'name' => 'Auditor',
                'slug' => 'auditor',
                'description' => 'Read-only access to audit and review system activities and data.',
            ],
            [
                'name' => 'Finance',
                'slug' => 'finance',
                'description' => 'Access to financial modules including accounting, budgeting, and reporting.',
            ],
            [
                'name' => 'Human Resources',
                'slug' => 'human-resources',
                'description' => 'Access to HR modules including employee management, payroll, and benefits.',
            ],
            [
                'name' => 'Sales',
                'slug' => 'sales',
                'description' => 'Access to sales modules including customer management, orders, and quotations.',
            ],
            [
                'name' => 'Procurement',
                'slug' => 'procurement',
                'description' => 'Access to procurement modules including purchasing, vendor management, and inventory.',
            ],
            [
                'name' => 'Manufacturing',
                'slug' => 'manufacturing',
                'description' => 'Access to manufacturing modules including production planning and shop floor management.',
            ],
            [
                'name' => 'Project Management',
                'slug' => 'project-management',
                'description' => 'Access to project management modules including tasks, timelines, and resources.',
            ],
            [
                'name' => 'IT',
                'slug' => 'it',
                'description' => 'Access to IT modules including system configuration and technical support.',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }

        $this->command->info('Default roles created successfully!');
    }
}
