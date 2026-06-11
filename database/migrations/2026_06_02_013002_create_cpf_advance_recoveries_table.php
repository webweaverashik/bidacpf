<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: table name `cpf_advance_recoveries` is long, so keep auto-generated
     * constraint/index names within MySQL's 64-char identifier limit by using
     * short explicit names where needed.
     */
    public function up(): void
    {
        Schema::create('cpf_advance_recoveries', function (Blueprint $table) {
            $table->id();
            $table->string('recovery_no')->unique();
            $table->foreignId('cpf_advance_id')->constrained();

            $table->date('recovery_date');
            $table->unsignedBigInteger('amount');

            // Allocation split recorded at approval (principal-first).
            $table->unsignedBigInteger('principal_applied')->default(0);
            $table->unsignedBigInteger('interest_applied')->default(0);

            // Deposit details captured by the officer.
            $table->date('deposit_date')->nullable();
            $table->string('deposit_reference')->nullable(); // bank slip / transaction no
            $table->string('bank_name')->nullable();

            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])
                ->default('draft');

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->text('remarks')->nullable();
            $table->text('reject_reason')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('rejected_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['cpf_advance_id', 'status'], 'car_advance_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpf_advance_recoveries');
    }
};
