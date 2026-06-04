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
        Schema::create('cpf_opening_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained();
            $table->date('effective_date');

            $table->unsignedBigInteger('self_contribution');
            $table->unsignedBigInteger('interest_amount');
            $table->unsignedBigInteger('outstanding_advance')->default(0);
            $table->unsignedBigInteger('net_balance');

            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('employee_id');
        });

            /*
                | Employee ID | Effective Date | Self Contribution | Government Contribution | Interest Amount | Outstanding Advance | Net Balance | Remarks               |
                | ----------- | -------------- | ----------------- | ----------------------- | --------------- | ------------------- | ----------- | --------------------- |
                | 1           | 01-Jul-2025    | 5000              | 4165                    | 100              | 20000               | 9265        | Onboarded with advance |
            */

            /*
            Ledger Integration
            When a employee is created, create a ledger entry:
            [
                'employee_id'      => $openingBalance->employee_id,
                'transaction_date' => $openingBalance->effective_date,
                'transaction_type' => LedgerTransactionType::OPENING_BALANCE,
                'source_type'      => SourceType::OPENING_BALANCE,
                'source_id'        => $openingBalance->id,
                'credit'           => $openingBalance->net_balance,
                'debit'            => 0,
                'balance'          => $openingBalance->net_balance,
            ]
            */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpf_opening_balances');
    }
};
