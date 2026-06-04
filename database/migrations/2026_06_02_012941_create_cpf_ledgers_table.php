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
        Schema::create('cpf_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('transaction_date');
            $table->string('transaction_type'); // App\LedgerTransactionType::value

            $table->string('source_type')->nullable(); // e.g. 'cpf_contributions', 'cpf_advances', 'cpf_advance_recoveries', 'bank_interest_distributions', etc.
            $table->unsignedBigInteger('source_id')->nullable(); // ID of the related record in the reference table
            $table->string('reference_no')->nullable();
            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('debit')->default(0);
            $table->unsignedBigInteger('credit')->default(0);
            $table->unsignedBigInteger('balance')->comment('Balance after this transaction. Calculated as previous balance + credit - debit.');

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['employee_id', 'transaction_date']);
            $table->index('transaction_type');
            $table->index(['source_type', 'source_id']);

            /*
            | Date        | Type                  | Debit | Credit | Balance |
            | ----------- | --------------------- | ----- | ------ | ------- |
            | 01-Jul-2025 | Opening Balance       | 0     | 100000 | 100000  |
            | 05-Jul-2025 | Employee Contribution | 0     | 5000   | 105000  |
            | 05-Jul-2025 | Govt Contribution     | 0     | 4165   | 109165  |
            | 10-Aug-2025 | Advance Disbursement  | 20000 | 0      | 89165   |
            | 05-Sep-2025 | Advance Recovery      | 0     | 2000   | 91165   |
            | 31-Dec-2025 | Bank Interest         | 0     | 1500   | 92665   |
            */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpf_ledgers');
    }
};
