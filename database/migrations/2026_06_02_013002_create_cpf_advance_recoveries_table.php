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
        Schema::create('cpf_advance_recoveries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cpf_advance_id')->constrained();

            $table->date('recovery_date');

            $table->unsignedBigInteger('amount');

            $table->string('deposit_slip')->nullable();

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpf_advance_recoveries');
    }
};
