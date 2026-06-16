<?php
namespace Database\Seeders;

use App\Enums\LedgerTransactionType;
use App\Enums\SourceType;
use App\Models\Auth\User;
use App\Models\Cpf\CpfOpeningBalance;
use App\Models\Employee\Employee;
use App\Services\Cpf\LedgerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds demo employees together with their CPF opening balances.
 *
 * For every employee we:
 *   1. Create the employee (pointing at a real National Pay Scale 2015 step).
 *   2. Create a CpfOpeningBalance snapshot.
 *   3. Post a single OPENING_BALANCE ledger entry via LedgerService so the
 *      running balance starts correctly. Every later transaction (monthly
 *      contributions, advances, interest) builds on top of this entry.
 *
 * Depends on: PayScale2015Seeder (steps must exist), UserSeeder (creator).
 */
class EmployeeSeeder extends Seeder
{
    /**
     * Number of demo employees to generate.
     */
    private const EMPLOYEE_COUNT = 60;

    public function __construct(private LedgerService $ledgerService)
    {}

    public function run(): void
    {
        // The admin user acts as the "creator" / poster for seeded records.
        $adminId = User::query()->value('id');

        if (! $adminId) {
            $this->command->warn('No users found — run UserSeeder first. Skipping EmployeeSeeder.');
            return;
        }

        DB::transaction(function () use ($adminId) {

            Employee::factory()
                ->count(self::EMPLOYEE_COUNT)
                ->create(['created_by' => $adminId])
                ->each(function (Employee $employee) use ($adminId) {

                    // 1. Build a consistent opening balance snapshot.
                    $openingBalance = CpfOpeningBalance::factory()->create([
                        'employee_id' => $employee->id,
                        'created_by'  => $adminId,
                    ]);

                    // 2. Post the opening ledger entry (credit = net balance).
                    //    This is the only place an OPENING_BALANCE row is written.
                    $this->ledgerService->create([
                        'employee_id'      => $employee->id,
                        'transaction_date' => $openingBalance->effective_date,
                        'transaction_type' => LedgerTransactionType::OPENING_BALANCE,
                        'source_type'      => SourceType::OPENING_BALANCE,
                        'source_id'        => $openingBalance->id,
                        'reference_no'     => 'OB-' . $employee->id,
                        'remarks'          => 'Opening balance migration',
                        'debit'            => 0,
                        'credit'           => $openingBalance->net_balance,
                        'created_by'       => $adminId,
                    ]);
                });
        });

        $this->command->info(self::EMPLOYEE_COUNT . ' employees seeded with opening balances.');
    }
}
