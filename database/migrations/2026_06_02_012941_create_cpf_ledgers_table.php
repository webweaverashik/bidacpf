<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cpf_ledgers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            $table->date('transaction_date');

            $table->string('transaction_type');

            $table->string('reference_type')->nullable();

            $table->unsignedBigInteger('reference_id')->nullable();

            $table->unsignedBigInteger('debit')->default(0);

            $table->unsignedBigInteger('credit')->default(0);

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();

            $table->index('employee_id');

            $table->index('transaction_date');

            $table->index('transaction_type');

            $table->index(['reference_type', 'reference_id']);
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
