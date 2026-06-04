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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('cpf_account_no')->unique();
            $table->string('name');
            $table->string('designation');
            $table->date('joining_date');
            $table->date('retirement_date')->nullable();

            $table->foreignId('pay_scale_id')->nullable()->constrained();
            $table->unsignedTinyInteger('grade');
            $table->unsignedTinyInteger('current_step');
            $table->unsignedInteger('current_basic_salary');

            $table->enum('status', ['active', 'retired', 'resigned', 'deceased'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index('cpf_account_no');
            $table->index('status');
            $table->index('grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
