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
            $table->string('fiscal_year', 9);
            $table->unsignedBigInteger('total_interest_amount');  // Total interest amount to be distributed
            $table->unsignedBigInteger('total_eligible_balance'); // Total eligible balance across all employees for the distribution
            $table->enum('status', ['draft', 'submitted', 'reversed'])->default('draft');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

            /*
            Create Batch
            ↓
            Preview Distribution
            ↓
            Verify
            ↓
            Submit
            */
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
