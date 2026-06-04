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
        Schema::create('cpf_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpf_contribution_batch_id')->constrained();
            $table->foreignId('employee_id')->constrained();
            $table->unsignedInteger('basic_salary');
            $table->unsignedInteger('employee_contribution');
            $table->unsignedInteger('government_contribution');

            $table->timestamps();

            $table->unique(['cpf_contribution_batch_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpf_contributions');
    }
};
