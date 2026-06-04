<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bank_interest_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_interest_batch_id')->constrained('bank_interest_batches', 'id', 'bid_batch_fk');
            $table->foreignId('employee_id')->constrained('employees', 'id', 'bid_employee_fk');
            $table->unsignedBigInteger('eligible_balance');
            $table->unsignedBigInteger('interest_amount');
            $table->json('calculation_snapshot')->nullable(); // Store the details of the interest calculation for reference
            /*
                Example of calculation_snapshot:
                {
                    "eligible_balance": 250000,
                    "fund_total": 20000000,
                    "ratio": 0.0125,
                    "calculated_interest": 3125.50,
                    "posted_interest": 3126,
                    "rounding_policy": "HALF_UP"
                }
            */

            $table->timestamps();
            $table->unique(['bank_interest_batch_id', 'employee_id'], 'bid_employee_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_interest_distributions');
    }
};
