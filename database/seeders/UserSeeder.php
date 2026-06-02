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
            'name'          => 'Ashfaq',
            'email'         => 'admin@cpf.bida.gov.bd',
            'mobile_number' => '01812778899',
            'password'      => Hash::make('password123'),
        ]);
        $superAdmin->assignRole('Super Admin');

        // CPF Officer
        $cpfOfficer = User::create([
            'name'          => 'Ashikur',
            'email'         => 'cpf.officer@cpf.bida.gov.bd',
            'mobile_number' => '01920869809',
            'password'      => Hash::make('password123'),
        ]);
        $cpfOfficer->assignRole('CPF Officer');

        // Accounts Officer
        $accountsOfficer = User::create([
            'name'          => 'Milon',
            'email'         => 'accounts.officer@cpf.bida.gov.bd',
            'mobile_number' => '01973033299',
            'password'      => Hash::make('password123'),
        ]);
        $accountsOfficer->assignRole('Accounts Officer');

        // Auditor
        $auditor = User::create([
            'name'          => 'Ramjan',
            'email'         => 'auditor@cpf.bida.gov.bd',
            'mobile_number' => '01723663310',
            'password'      => Hash::make('password123'),
        ]);
        $auditor->assignRole('Auditor');
    }
}
