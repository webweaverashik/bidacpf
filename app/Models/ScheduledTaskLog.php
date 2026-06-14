<?php
namespace App\Models;

use Illuminate\Support\Str;

/**
 * A single run of a scheduled (cron) / recurring command.
 *
 * Populated by App\Listeners\ScheduledTaskLogger. Read-only from the app's
 * perspective — surfaced to admins on the "Scheduled Tasks" log page. No
 * activity logging on this model itself (it IS the operational log).
 *
 * `command` keeps the raw artisan command (for traceability); `label` is the
 * human-friendly name shown to users.
 */
class ScheduledTaskLog extends BaseModel
{
    public const STATUS_RUNNING   = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_SKIPPED   = 'skipped';

    /** Friendly names for known commands (keyed by base command name). */
    public const LABELS = [
        'cpf:generate-contribution-batch' => 'Monthly Contribution Draft',
        'cpf:annual-increment'            => 'Annual Increment',
        'backup:run'                      => 'Database Backup',
        'backup:clean'                    => 'Backup Cleanup (Database)',
        'backup:clean-files'              => 'File Backup Cleanup',
    ];

    protected $fillable = [
        'command', 'label', 'status', 'exit_code', 'runtime', 'output', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at'  => 'datetime',
            'finished_at' => 'datetime',
            'runtime'     => 'decimal:3',
            'exit_code'   => 'integer',
        ];
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Friendly name for a command string. Unknown commands are humanised
     * (e.g. "queue:work" → "Queue Work") so they still read sensibly.
     */
    public static function labelFor(?string $command): string
    {
        $command = (string) $command;
        $base    = trim(explode(' ', $command)[0]); // drop any options

        return self::LABELS[$base] ?? Str::headline(str_replace([':', '-', '_'], ' ', $base));
    }

    /** The name to show users (stored label, or resolved from the command). */
    public function displayName(): string
    {
        return $this->label ?: self::labelFor($this->command);
    }

    /** Bootstrap badge class for the status. */
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'badge-light-success',
            self::STATUS_FAILED    => 'badge-light-danger',
            self::STATUS_SKIPPED   => 'badge-light-warning',
            default                => 'badge-light-primary', // running
        };
    }

    /** "1.234 s" or — when unknown. */
    public function runtimeForHumans(): string
    {
        return $this->runtime !== null ? rtrim(rtrim((string) $this->runtime, '0'), '.') . ' s' : '—';
    }
}
