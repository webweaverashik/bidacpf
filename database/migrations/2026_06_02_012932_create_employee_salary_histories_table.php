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
        Schema::create('employee_salary_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pay_scale_step_id')->constrained();
            $table->date('effective_date');
            $table->enum('change_type', ['initial', 'annual_increment', 'promotion', 'revision']);
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('employee_id');
            $table->index('effective_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salary_histories');
    }
};
