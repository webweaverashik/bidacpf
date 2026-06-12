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
        Schema::create('cpf_final_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_no')->unique(); // STL-YYYY-00001
            $table->foreignId('employee_id')->constrained();

                                               // Reason for separation → drives the employee status transition and
                                               // the certificate wording (retirement | resignation | deceased).
            $table->string('settlement_type'); // App\Enums\SettlementType::value

            // When the settlement was initiated (officer draft).
            $table->date('application_date');

            // Effective date of separation (last working day / retirement date /
            // date of death). The FINAL_SETTLEMENT ledger entry is posted on this date.
            $table->date('settlement_date');

            /*
            |------------------------------------------------------------------
            | Financial snapshot
            |------------------------------------------------------------------
            | Captured at draft for preview, finalised at approval. All values
            | are whole BDT integers, consistent with the rest of the system.
            */

            // Net CPF balance available at settlement: the running ledger balance,
            // which is already net of any disbursed advance principal.
            $table->unsignedBigInteger('closing_balance')->default(0);

            // Combined principal + interest still outstanding on any open advance
            // at the moment of settlement. Recorded for the loan-clearance certificate.
            $table->unsignedBigInteger('outstanding_advance')->default(0);

            // Amount actually deducted from the payable to clear outstanding
            // advances (policy-driven; finalised at approval). May be 0 when the
            // disbursement debit has already netted the advance from the balance.
            $table->unsignedBigInteger('advance_adjustment')->default(0);

            // Final amount payable to the member / nominee.
            $table->unsignedBigInteger('total_payable')->default(0);

            /*
            |------------------------------------------------------------------
            | Payee — the member by default; the nominee for a deceased member
            |------------------------------------------------------------------
            */
            $table->string('payee_name')->nullable();
            $table->string('payee_relation')->nullable(); // e.g. Self, Spouse, Son
            $table->text('payee_detail')->nullable();     // address / bank account / notes

            /*
            |------------------------------------------------------------------
            | Approval workflow (officer → admin) — mirrors cpf_advances
            |------------------------------------------------------------------
            */
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])
                ->default('draft');

            $table->date('approval_date')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->text('remarks')->nullable();
            $table->text('reject_reason')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('rejected_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status']);
            $table->index('settlement_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpf_final_settlements');
    }
};
