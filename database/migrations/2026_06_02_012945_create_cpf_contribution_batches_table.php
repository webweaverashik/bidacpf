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
            $table->date('contribution_month'); //Contribution Month, Example: 2026-07-01 = July 2026 Contribution
            $table->string('fiscal_year', 9);
            $table->enum('status', ['draft', 'submitted', 'reversed'])->default('draft');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->softDeletes();
            $table->timestamps();

            $table->unique('contribution_month'); //One batch per month
            $table->index('fiscal_year');
            $table->index('status');
            $table->index('submitted_at');
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
