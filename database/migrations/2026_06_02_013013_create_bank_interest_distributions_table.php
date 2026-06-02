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

            $table->foreignId('bank_interest_batch_id')->constrained();

            $table->foreignId('employee_id')->constrained();

            $table->unsignedBigInteger('eligible_balance');

            $table->unsignedBigInteger('interest_amount');

            $table->json('calculation_snapshot')->nullable();

            $table->timestamps();
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
