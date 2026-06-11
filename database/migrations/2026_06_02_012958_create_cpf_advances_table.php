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
        Schema::create('cpf_advances', function (Blueprint $table) {
            $table->id();
            $table->string('advance_no')->unique();
            $table->foreignId('employee_id')->constrained();

            $table->date('application_date');

            // Officer's requested amount (entered at draft). Strictly capped by
            // the system advance limit; never customised per employee.
            $table->unsignedBigInteger('requested_amount');

            // Admin-finalised amount (may differ from requested). Null until approved.
            $table->unsignedBigInteger('approved_amount')->nullable();

            // Snapshotted policy values for this specific loan.
            $table->decimal('interest_rate', 5, 2);

            // Interest = rate% of approved principal. It is repaid as part of the
            // installment schedule (principal-first allocation), not gifted.
            $table->unsignedBigInteger('interest_amount')->default(0);
            $table->boolean('interest_credited')->default(false);   // true once interest fully recovered
            $table->timestamp('interest_credited_at')->nullable();

            // Recovery schedule. Total repayable = approved_amount + interest_amount.
            $table->unsignedTinyInteger('installment_count');
            $table->unsignedBigInteger('installment_amount')->default(0);    // per-installment of the TOTAL, recalculated after each recovery
            $table->unsignedBigInteger('principal_outstanding')->default(0); // principal yet to recover
            $table->unsignedBigInteger('interest_outstanding')->default(0);  // interest yet to recover
            $table->unsignedBigInteger('outstanding_amount')->default(0);    // combined = principal_outstanding + interest_outstanding

            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'completed'])
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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpf_advances');
    }
};
