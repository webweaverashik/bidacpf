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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('cpf_account_no')->unique();
            $table->string('name');
            $table->string('designation');

            $table->string('email')->nullable();
            $table->string('mobile_number', 20)->nullable();
            $table->string('photo')->nullable();
            
            $table->date('joining_date');
            $table->date('retirement_date')->nullable();
            
            $table->foreignId('pay_scale_step_id')->constrained()->comment('Current pay scale grade & step');
            
            $table->enum('status', ['active', 'retired', 'resigned', 'deceased'])->default('active');
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->constrained('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
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
