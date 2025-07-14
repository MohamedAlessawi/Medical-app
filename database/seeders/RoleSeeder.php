<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;


class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['patient', 'secretary', 'doctor', 'admin', 'super_admin'];
        foreach ($roles as $role) {
            Role::create(['name' => $role, 'description' => ucfirst($role)]);
        }
    }
}
