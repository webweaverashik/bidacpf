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
        Schema::create('pay_scales', function (Blueprint $table) {
            $table->id();

            $table->string('name')->comment('National Pay Scale 2015');

            $table->year('effective_year');

            $table->date('effective_from');

            $table->date('effective_to')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('effective_year');
            $table->index('is_active');
        });

        Schema::create('pay_scale_grades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pay_scale_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('grade');

            $table->unsignedTinyInteger('step');

            $table->unsignedInteger('basic_salary');

            $table->timestamps();

            $table->unique(['pay_scale_id', 'grade', 'step']);

            $table->index('grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_scales');
        Schema::dropIfExists('pay_scale_grades');
    }
};
