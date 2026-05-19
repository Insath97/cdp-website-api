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

            /* Activity Log Management */
            ['name' => 'Activity Log Index',  'group_name' => 'Activity Log Permissions'],
            ['name' => 'Activity Log Show',   'group_name' => 'Activity Log Permissions'],

            /* CMS Management */
            ['name' => 'CMS Index',  'group_name' => 'CMS Management Permissions'],
            ['name' => 'CMS Update', 'group_name' => 'CMS Management Permissions'],

            /* Contact Management */
            ['name' => 'Contact Index',  'group_name' => 'Contact Permissions'],
            ['name' => 'Contact Show',   'group_name' => 'Contact Permissions'],
            ['name' => 'Contact Reply',  'group_name' => 'Contact Permissions'],
            ['name' => 'Contact Delete', 'group_name' => 'Contact Permissions'],

            /* Contact Type Management */
            ['name' => 'Contact Type Index',  'group_name' => 'Contact Type Permissions'],
            ['name' => 'Contact Type Create', 'group_name' => 'Contact Type Permissions'],
            ['name' => 'Contact Type Update', 'group_name' => 'Contact Type Permissions'],
            ['name' => 'Contact Type Delete', 'group_name' => 'Contact Type Permissions'],

            /* Branch Management */
            ['name' => 'Branch Index',  'group_name' => 'Branch Permissions'],
            ['name' => 'Branch Create', 'group_name' => 'Branch Permissions'],
            ['name' => 'Branch Update', 'group_name' => 'Branch Permissions'],
            ['name' => 'Branch Delete', 'group_name' => 'Branch Permissions'],

            /* Setting Management */
            ['name' => 'Setting Index',  'group_name' => 'Setting Permissions'],
            ['name' => 'Setting Update', 'group_name' => 'Setting Permissions'],
            ['name' => 'Database Export', 'group_name' => 'Setting Permissions'],

            /* Service Management */
            ['name' => 'Service Index',  'group_name' => 'Service Permissions'],
            ['name' => 'Service Create', 'group_name' => 'Service Permissions'],
            ['name' => 'Service Update', 'group_name' => 'Service Permissions'],
            ['name' => 'Service Delete', 'group_name' => 'Service Permissions'],
            ['name' => 'Service Toggle Active', 'group_name' => 'Service Permissions'],

            /* Event Management */
            ['name' => 'Event Index', 'group_name' => 'Event Permissions'],
            ['name' => 'Event Create', 'group_name' => 'Event Permissions'],
            ['name' => 'Event Update', 'group_name' => 'Event Permissions'],
            ['name' => 'Event Delete', 'group_name' => 'Event Permissions'],
            ['name' => 'Event Soft Delete', 'group_name' => 'Event Permissions'],
            ['name' => 'Event Force Delete', 'group_name' => 'Event Permissions'],
            ['name' => 'Event Restore', 'group_name' => 'Event Permissions'],
            ['name' => 'Event Toggle Active', 'group_name' => 'Event Permissions'],
            ['name' => 'Event Approve', 'group_name' => 'Event Permissions'],

            /* Plan Management */
            ['name' => 'Plan Index',  'group_name' => 'Plan Permissions'],
            ['name' => 'Plan Create', 'group_name' => 'Plan Permissions'],
            ['name' => 'Plan Update', 'group_name' => 'Plan Permissions'],
            ['name' => 'Plan Delete', 'group_name' => 'Plan Permissions'],
            ['name' => 'Plan Toggle Active', 'group_name' => 'Plan Permissions'],

            /* Career Management */
            ['name' => 'Career Index',          'group_name' => 'Career Permissions'],
            ['name' => 'Career Create',         'group_name' => 'Career Permissions'],
            ['name' => 'Career Update',         'group_name' => 'Career Permissions'],
            ['name' => 'Career Delete',         'group_name' => 'Career Permissions'],
            ['name' => 'Career Soft Delete',    'group_name' => 'Career Permissions'],
            ['name' => 'Career Force Delete',   'group_name' => 'Career Permissions'],
            ['name' => 'Career Restore',        'group_name' => 'Career Permissions'],
            ['name' => 'Career Toggle Active',  'group_name' => 'Career Permissions'],

            /* Career Application Management */
            ['name' => 'Career Application Index',          'group_name' => 'Career Application Permissions'],
            ['name' => 'Career Application Show',           'group_name' => 'Career Application Permissions'],
            ['name' => 'Career Application Update Status',  'group_name' => 'Career Application Permissions'],
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
