<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Step 1: Create Roles
        // $adminRole      = Role::firstOrCreate(['name' => 'admin']);
        // $managerRole    = Role::firstOrCreate(['name' => 'manager']);
        // $accountantRole = Role::firstOrCreate(['name' => 'accountant']);

        // Step 2: Create Users and Assign Roles

        // Super Admin
        $superAdmin = User::create([
            'name'          => 'Ashik',
            'email'         => 'webweaverashik@gmail.com',
            'mobile_number' => '01812778899',
            'password'      => Hash::make('password123'),
        ]);
        $superAdmin->assignRole('Admin');

        // CPF Officer
        $cpfOfficer = User::create([
            'name'          => 'Rahman',
            'email'         => 'ashik.ane.doict@gmail.com',
            'mobile_number' => '01920869809',
            'password'      => Hash::make('password123'),
        ]);
        $cpfOfficer->assignRole('CPF Officer');

        // Auditor
        $auditor = User::create([
            'name'          => 'Ramjan',
            'email'         => 'ashikgsc170@gmail.com',
            'mobile_number' => '01723663310',
            'password'      => Hash::make('password123'),
        ]);
        $auditor->assignRole('Auditor');
    }
}
