<?php
namespace App\Listeners;

use App\Models\ScheduledTaskLog;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Records runs of the application's recurring/maintenance commands into
 * scheduled_task_logs so admins can see operational history — did the daily
 * backup run, did the monthly contribution draft succeed, how long it took.
 *
 * Why command events (not scheduler events):
 *   The framework's ScheduledTask* events only fire when the SCHEDULER
 *   dispatches a task (php artisan schedule:run / schedule:work). Running a
 *   command directly (php artisan cpf:annual-increment) never fires them.
 *   CommandStarting/CommandFinished fire in BOTH cases — when run manually and
 *   when the scheduler runs the command in its child artisan process — so a run
 *   is captured exactly once either way.
 *
 * Only the WATCHED commands are logged, to keep noise out (migrate, route:clear,
 * tinker, etc. are ignored). Add a command's name to WATCHED to track it.
 *
 * Register once in a service provider (e.g. AppServiceProvider::boot):
 *
 *     \Illuminate\Support\Facades\Event::subscribe(\App\Listeners\ScheduledTaskLogger::class);
 *
 * Every handler is defensive: a logging failure must never abort the actual
 * command, so all DB writes are wrapped and swallowed (with a warning).
 */
class ScheduledTaskLogger
{
    /** Command names to record (match by name; options are ignored for matching). */
    public const WATCHED = [
        'cpf:generate-contribution-batch',
        'cpf:annual-increment',
        'backup:run',
        'backup:clean',
        'backup:clean-files',
    ];

    /** In-process map: command name => ['id' => log id, 'start' => float seconds]. */
    protected array $running = [];

    public function onCommandStarting(CommandStarting $event): void
    {
        if (! $this->watched($event->command)) {
            return;
        }

        try {
            $log = ScheduledTaskLog::create([
                'command'    => $this->describe($event->command, $event->input ?? null),
                'label'      => ScheduledTaskLog::labelFor($event->command),
                'status'     => ScheduledTaskLog::STATUS_RUNNING,
                'started_at' => now(),
            ]);

            $this->running[$event->command] = ['id' => $log->id, 'start' => microtime(true)];
        } catch (\Throwable $e) {
            $this->warn('starting', $e);
        }
    }

    public function onCommandFinished(CommandFinished $event): void
    {
        if (! $this->watched($event->command)) {
            return;
        }

        try {
            $meta = $this->running[$event->command] ?? null;
            unset($this->running[$event->command]);

            $exit    = $event->exitCode;
            $runtime = $meta ? round(microtime(true) - $meta['start'], 3) : null;

            $data = [
                'status'      => ($exit === null || $exit === 0)
                    ? ScheduledTaskLog::STATUS_COMPLETED
                    : ScheduledTaskLog::STATUS_FAILED,
                'exit_code'   => $exit,
                'runtime'     => $runtime,
                'finished_at' => now(),
            ];

            $log = $meta ? ScheduledTaskLog::find($meta['id']) : null;

            if ($log) {
                $log->update($data);
            } else {
                // "starting" wasn't captured (e.g. nested call) — record it anyway.
                ScheduledTaskLog::create(array_merge([
                    'command'    => $this->describe($event->command, $event->input ?? null),
                    'label'      => ScheduledTaskLog::labelFor($event->command),
                    'started_at' => now(),
                ], $data));
            }
        } catch (\Throwable $e) {
            $this->warn('finished', $e);
        }
    }

    /** Whether this command name should be logged. */
    protected function watched(?string $command): bool
    {
        return $command !== null && in_array($command, self::WATCHED, true);
    }

    /**
     * Command name plus any non-default option flags, when readable.
     * Falls back to just the name.
     */
    protected function describe(string $command, $input): string
    {
        try {
            if ($input) {
                $flags = [];
                foreach ($input->getOptions() as $name => $value) {
                    if ($value === false || $value === null || $value === []
                        || in_array($name, ['help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction', 'env'], true)) {
                        continue;
                    }
                    $flags[] = $value === true ? "--{$name}" : "--{$name}={$value}";
                }
                if ($flags) {
                    return Str::limit($command . ' ' . implode(' ', $flags), 250);
                }
            }
        } catch (\Throwable $e) {
            // Ignore — fall back to the bare command name.
        }

        return $command;
    }

    protected function warn(string $phase, \Throwable $e): void
    {
        Log::warning("ScheduledTaskLogger[{$phase}] could not record run: " . $e->getMessage());
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            CommandStarting::class => 'onCommandStarting',
            CommandFinished::class => 'onCommandFinished',
        ];
    }
}
