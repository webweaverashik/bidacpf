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
        Schema::create('pay_scale_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pay_scale_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('grade');
            $table->unsignedTinyInteger('step');
            $table->unsignedInteger('basic_salary');
            $table->timestamps();

            $table->unique(['pay_scale_id', 'grade', 'step']);
            $table->index(['pay_scale_id', 'grade']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_scale_steps');
    }
};
