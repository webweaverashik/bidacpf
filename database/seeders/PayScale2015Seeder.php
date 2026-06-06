<?php
namespace Database\Seeders;

use App\Models\Employee\PayScale;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayScale2015Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Fix 1: correct directory (seed-data) and filename (kebab-case)
            $path = database_path('seeders/data/pay_scale_2015.json');

            $data = json_decode(file_get_contents($path), true);

            $payScale = PayScale::updateOrCreate(
                [
                    'effective_year' => $data['effective_year'],
                ],
                [
                    'name'           => $data['name'],
                    'total_grades'   => count($data['grades']), // Fix 2: populate required column (20)
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
                        'step'         => $index + 1, // steps are 1-indexed
                        'basic_salary' => $salary,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];
                }
            }

            // Fix 3: correct table name (pay_scale_steps, not pay_scale_grades)
            DB::table('pay_scale_steps')->upsert(
                $rows,
                ['pay_scale_id', 'grade', 'step'], // unique key for upsert
                ['basic_salary', 'updated_at'],    // columns to update on conflict
            );
        });
    }
}
