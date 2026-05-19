<?php

namespace App\Jobs;

use App\Models\CommandRunLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class RunLegacyCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        private readonly int $logId,
        private readonly string $command,
        private readonly array $params,
    ) {}

    public function handle(): void
    {
        $log = CommandRunLog::findOrFail($this->logId);

        try {
            $exitCode = Artisan::call($this->command, $this->params);
            $output = Artisan::output();
        } catch (\Throwable $e) {
            $log->update([
                'exit_code' => null,
                'output' => $e->getMessage(),
                'success' => false,
            ]);

            return;
        }

        $log->update([
            'exit_code' => $exitCode,
            'output' => $output,
            'success' => $exitCode === 0,
        ]);
    }
}
