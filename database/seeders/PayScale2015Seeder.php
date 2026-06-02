<?php
namespace Database\Seeders;

use App\Models\PayScale;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayScale2015Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $path = database_path('seeders/data/pay_scale_2015.json');

            $data = json_decode(file_get_contents($path), true);

            $payScale = PayScale::updateOrCreate(
                [
                    'effective_year' => $data['effective_year'],
                ],
                [
                    'name'           => $data['name'],
                    'effective_from' => $data['effective_from'],
                    'is_active'      => true,
                ],
            );

            $rows = [];

            foreach ($data['grades'] as $grade => $steps) {
                foreach ($steps as $index => $salary) {
                    $rows[] = [
                        'pay_scale_id' => $payScale->id,
                        'grade'        => (int) $grade,
                        'step'         => $index + 1,
                        'basic_salary' => $salary,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];
                }
            }

            DB::table('pay_scale_grades')->upsert($rows, ['pay_scale_id', 'grade', 'step'], ['basic_salary', 'updated_at']);
        });
    }
}
