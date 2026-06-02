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
        Schema::create('bank_interest_batches', function (Blueprint $table) {
            $table->id();

            $table->date('distribution_date');

            $table->unsignedBigInteger('total_interest_amount');

            $table->unsignedBigInteger('total_eligible_balance');

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_interest_batches');
    }
};
