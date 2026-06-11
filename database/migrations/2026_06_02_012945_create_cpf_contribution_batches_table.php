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
        Schema::create('cpf_contribution_batches', function (Blueprint $table) {
            $table->id();
                                                // Contribution period
            $table->date('contribution_month'); // Example: 2026-07-01 = July 2026 Contribution
            $table->string('fiscal_year', 9);

            // Contribution rates snapshot
            $table->decimal('employee_rate', 5, 2)->nullable();
            $table->decimal('government_rate', 5, 2)->nullable();

            // Workflow
            $table->enum('status', ['draft', 'submitted', 'approved', 'reversed'])->default('draft');
            $table->text('remarks')->nullable();

            // Submission
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();

            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Reversal
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();

            // Audit
            $table->foreignId('created_by')->constrained('users');

            $table->softDeletes();
            $table->timestamps();

                                                  // Constraints
            $table->unique('contribution_month'); // One batch per month

            // Indexes
            $table->index('fiscal_year');
            $table->index('status');
            $table->index('submitted_at');
            $table->index('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpf_contribution_batches');
    }
};
