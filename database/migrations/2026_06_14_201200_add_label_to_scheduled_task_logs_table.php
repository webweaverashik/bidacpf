<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Friendly labels for the known recurring commands. */
    private array $labels = [
        'cpf:generate-contribution-batch' => 'Monthly Contribution Draft',
        'cpf:annual-increment'            => 'Annual Increment',
        'backup:run'                      => 'Database Backup',
        'backup:clean'                    => 'Backup Cleanup (Database)',
        'backup:clean-files'              => 'File Backup Cleanup',
    ];

    public function up(): void
    {
        Schema::table('scheduled_task_logs', function (Blueprint $table) {
            $table->string('label')->nullable()->after('command');
        });

        // Backfill labels for any rows already recorded (command may carry options).
        foreach ($this->labels as $command => $label) {
            DB::table('scheduled_task_logs')
                ->where(function ($q) use ($command) {
                    $q->where('command', $command)
                        ->orWhere('command', 'like', $command . ' %');
                })
                ->update(['label' => $label]);
        }
    }

    public function down(): void
    {
        Schema::table('scheduled_task_logs', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};
