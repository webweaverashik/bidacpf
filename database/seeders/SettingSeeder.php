<?php
namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [

            [
                'key'         => 'employee_contribution_rate',
                'value'       => '10',
                'description' => 'Employee CPF Contribution Percentage',
            ],

            [
                'key'         => 'government_contribution_rate',
                'value'       => '8.33',
                'description' => 'Government CPF Contribution Percentage',
            ],

            [
                'key'         => 'advance_limit_percentage',
                'value'       => '80',
                'description' => 'Maximum CPF Advance Percentage',
            ],

            [
                'key'         => 'advance_interest_rate',
                'value'       => '5',
                'description' => 'CPF Advance Interest Percentage',
            ],

            [
                'key'         => 'max_installments',
                'value'       => '48',
                'description' => 'Maximum Advance Recovery Installments',
            ],

            // [
            //     'key'         => 'interest_distribution_months',
            //     'value'       => json_encode([
            //         'June',
            //         'December',
            //     ]),
            //     'description' => 'Bank interest distribution months',
            // ],
        ];

        foreach ($settings as $setting) {

            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
