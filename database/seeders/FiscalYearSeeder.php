<?php
namespace Database\Seeders;

use App\Models\FiscalYear;
use Illuminate\Database\Seeder;

class FiscalYearSeeder extends Seeder
{
    public function run(): void
    {
        $years = [
            [
                'title'      => '2024-2025',
                'start_date' => '2024-07-01',
                'end_date'   => '2025-06-30',
                'is_active'  => false,
            ],
            [
                'title'      => '2025-2026',
                'start_date' => '2025-07-01',
                'end_date'   => '2026-06-30',
                'is_active'  => true,
            ],
        ];

        foreach ($years as $year) {
            FiscalYear::updateOrCreate(
                ['title' => $year['title']],
                $year
            );
        }
    }
}
