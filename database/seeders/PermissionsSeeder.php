<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            /* Access Management */
            ['name' => 'Permission Index',  'group_name' => 'Access Management Permissions'],
            ['name' => 'Permission Create', 'group_name' => 'Access Management Permissions'],
            ['name' => 'Permission Update', 'group_name' => 'Access Management Permissions'],
            ['name' => 'Permission Delete', 'group_name' => 'Access Management Permissions'],
            ['name' => 'Role Index',  'group_name' => 'Access Management Permissions'],
            ['name' => 'Role Create', 'group_name' => 'Access Management Permissions'],
            ['name' => 'Role Update', 'group_name' => 'Access Management Permissions'],
            ['name' => 'Role Delete', 'group_name' => 'Access Management Permissions'],

            /* User Management */
            ['name' => 'User Index',  'group_name' => 'User Permissions'],
            ['name' => 'User Create', 'group_name' => 'User Permissions'],
            ['name' => 'User Update', 'group_name' => 'User Permissions'],
            ['name' => 'User Delete', 'group_name' => 'User Permissions'],

            /* Category Management */
            ['name' => 'Category Index',  'group_name' => 'Category Permissions'],
            ['name' => 'Category Create', 'group_name' => 'Category Permissions'],
            ['name' => 'Category Update', 'group_name' => 'Category Permissions'],
            ['name' => 'Category Delete', 'group_name' => 'Category Permissions'],

            /* Course Management */
            ['name' => 'Course Index', 'group_name' => 'Course Permissions'],
            ['name' => 'Course Create', 'group_name' => 'Course Permissions'],
            ['name' => 'Course Update', 'group_name' => 'Course Permissions'],
            ['name' => 'Course Soft Delete', 'group_name' => 'Course Permissions'],
            ['name' => 'Course Force Delete', 'group_name' => 'Course Permissions'],
            ['name' => 'Course Restore', 'group_name' => 'Course Permissions'],
            ['name' => 'Course Toggle Active', 'group_name' => 'Course Permissions'],
            ['name' => 'Course Toggle Registration', 'group_name' => 'Course Permissions'],
            ['name' => 'Course Toggle New', 'group_name' => 'Course Permissions'],

            /* Activity Log Management */
            ['name' => 'Activity Log Index',  'group_name' => 'Activity Log Permissions'],
            ['name' => 'Activity Log Show',   'group_name' => 'Activity Log Permissions'],

            /* Certification Management */
            ['name' => 'Certification Index',  'group_name' => 'Certification Permissions'],
            ['name' => 'Certification Create', 'group_name' => 'Certification Permissions'],
            ['name' => 'Certification Update', 'group_name' => 'Certification Permissions'],
            ['name' => 'Certification Soft Delete', 'group_name' => 'Certification Permissions'],
            ['name' => 'Certification Force Delete', 'group_name' => 'Certification Permissions'],
            ['name' => 'Certification Restore', 'group_name' => 'Certification Permissions'],
            ['name' => 'Certification Toggle Active', 'group_name' => 'Certification Permissions'],
            ['name' => 'Certification Import', 'group_name' => 'Certification Permissions'],

            /* CMS Management */
            ['name' => 'CMS Index',  'group_name' => 'CMS Management Permissions'],
            ['name' => 'CMS Update', 'group_name' => 'CMS Management Permissions'],

            /* Pathway Management */
            ['name' => 'Pathway Index',         'group_name' => 'Pathway Permissions'],
            ['name' => 'Pathway Create',        'group_name' => 'Pathway Permissions'],
            ['name' => 'Pathway Update',        'group_name' => 'Pathway Permissions'],
            ['name' => 'Pathway Delete',        'group_name' => 'Pathway Permissions'],
            ['name' => 'Pathway Toggle Active', 'group_name' => 'Pathway Permissions'],

            /* Registration Program Management */
            ['name' => 'Registration Program Index',         'group_name' => 'Registration Program Permissions'],
            ['name' => 'Registration Program Create',        'group_name' => 'Registration Program Permissions'],
            ['name' => 'Registration Program Update',        'group_name' => 'Registration Program Permissions'],
            ['name' => 'Registration Program Delete',        'group_name' => 'Registration Program Permissions'],
            ['name' => 'Registration Program Toggle Active', 'group_name' => 'Registration Program Permissions'],

            /* Registration Management */
            ['name' => 'Registration Index',   'group_name' => 'Registration Permissions'],
            ['name' => 'Registration Show',    'group_name' => 'Registration Permissions'],
            ['name' => 'Registration Approve', 'group_name' => 'Registration Permissions'],
            ['name' => 'Registration Reject',  'group_name' => 'Registration Permissions'],
            ['name' => 'Registration Delete',  'group_name' => 'Registration Permissions'],

            /* Contact Management */
            ['name' => 'Contact Index',  'group_name' => 'Contact Permissions'],
            ['name' => 'Contact Show',   'group_name' => 'Contact Permissions'],
            ['name' => 'Contact Reply',  'group_name' => 'Contact Permissions'],
            ['name' => 'Contact Delete', 'group_name' => 'Contact Permissions'],

            /* Setting Management */
            ['name' => 'Setting Index',  'group_name' => 'Setting Permissions'],
            ['name' => 'Setting Update', 'group_name' => 'Setting Permissions'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission['name'],
                'group_name' => $permission['group_name'],
                'guard_name' => 'api',
            ]);
        }

        $role = Role::firstOrCreate(['guard_name' => 'api', 'name' => 'Super Admin']);

        $allPermissions = Permission::all();
        $role->syncPermissions($allPermissions);
    }
}
