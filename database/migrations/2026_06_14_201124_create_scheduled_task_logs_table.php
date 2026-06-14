<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_task_logs', function (Blueprint $table) {
            $table->id();

            $table->string('command');                    // e.g. cpf:annual-increment, backup:run --only-db
            $table->string('description')->nullable();    // scheduled task description, when set
            $table->string('status')->default('running'); // running | completed | failed | skipped
            $table->integer('exit_code')->nullable();
            $table->decimal('runtime', 10, 3)->nullable(); // seconds
            $table->longText('output')->nullable();        // captured output / exception message

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();

            $table->index('command');
            $table->index('status');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_task_logs');
    }
};
