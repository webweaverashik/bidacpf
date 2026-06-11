<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bank interest distribution batch. One batch per bi-annual cut-off date
     * (30 June / 31 December). Mirrors the cpf_contribution_batches lifecycle:
     * draft -> submitted -> approved | (rejected -> draft) -> reversed.
     */
    public function up(): void
    {
        Schema::create('bank_interest_batches', function (Blueprint $table) {
            $table->id();

            // Cut-off date the distribution is calculated against (30 Jun / 31 Dec)
            $table->date('distribution_date');
            $table->string('fiscal_year', 9);

            // Total bank interest received (whole BDT) + the eligible fund base
            // (sum of every member's CPF balance on the cut-off date) used to
            // pro-rate. total_eligible_balance is filled when distributions are
            // computed, so it defaults to 0.
            $table->unsignedBigInteger('total_interest_amount');
            $table->unsignedBigInteger('total_eligible_balance')->default(0);

            // Workflow
            $table->enum('status', ['draft', 'submitted', 'approved', 'reversed'])->default('draft');
            $table->text('remarks')->nullable();

            // Submission (officer)
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();

            // Approval (admin) — point of record; ledger posted here
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Reversal (admin)
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();

            // Audit
            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();

            // One distribution per cut-off date. Explicit short name keeps us
            // well under MySQL's 64-char identifier limit.
            $table->unique('distribution_date', 'bib_distribution_date_unique');

            $table->index('fiscal_year');
            $table->index('status');
            $table->index('submitted_at');
            $table->index('approved_at');

            /*
            Create Batch (auto-computes preview distributions)
            ↓
            Review / Regenerate  (DRAFT, editable)
            ↓
            Submit               (SUBMITTED, locked, awaiting admin)
            ↓
            Approve  → posts BANK_INTEREST ledger credits (APPROVED)
            Reject   → back to DRAFT for correction
            ↓
            Reverse  → posts mirror debit entries (REVERSED)
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
