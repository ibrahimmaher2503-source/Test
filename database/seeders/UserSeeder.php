<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branch = Branch::first();

        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'branch_id' => null,
        ]);
        $superAdmin->assignRole(User::ROLE_SUPER_ADMIN);

        $branchManager = User::factory()->create([
            'name' => 'Branch Manager',
            'email' => 'manager@example.com',
            'branch_id' => $branch->id,
        ]);
        $branchManager->assignRole(User::ROLE_BRANCH_MANAGER);

        $counter = User::factory()->create([
            'name' => 'Counter',
            'email' => 'counter@example.com',
            'branch_id' => $branch->id,
        ]);
        $counter->assignRole(User::ROLE_COUNTER);

        $counterTwo = User::factory()->create([
            'name' => 'Counter Two',
            'email' => 'counter2@example.com',
            'branch_id' => $branch->id,
        ]);
        $counterTwo->assignRole(User::ROLE_COUNTER);
    }
}
