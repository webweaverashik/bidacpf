<?php
namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Master seeder.
 *
 * Order is significant:
 *   1. Roles & permissions   — must exist before users get roles.
 *   2. Users                 — admin acts as creator/poster for demo data.
 *   3. Pay scale             — employees reference real pay scale steps.
 *   4. Settings              — contribution rates etc. used by services.
 *   5. Employees             — created with opening balances + ledger.
 *   6. Contributions         — monthly batches posted via service.
 *   7. Advances              — disbursed/recovered via service.
 *   8. Interest              — distributed last, over final balances.
 *
 * Steps 5–8 are DEMO data. For a production deploy, run only steps 1–4
 * (comment out the demo block below).
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ---- Core / production-required seed data ----
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            PayScale2015Seeder::class,
            SettingSeeder::class,
        ]);

        // ---- Demo data (safe to skip in production) ----
        $this->call([
            EmployeeSeeder::class,
            ContributionDemoSeeder::class,
            AdvanceDemoSeeder::class,
            InterestDemoSeeder::class,
        ]);
    }
}
