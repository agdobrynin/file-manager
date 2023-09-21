<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\MoveFileBetweenStorageInterface;
use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Throwable;

class MoveFileToCloud implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $currentRetryCount = 1;

    public readonly int $maxRetries;

    public readonly int $backoffFactor;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly File $file)
    {
        $this->onQueue('upload');
        $this->maxRetries = 100;
        $this->backoffFactor = 10;
    }

    public function middleware(): array
    {
        return [new RateLimited(self::class)];
    }

    /**
     * Execute the job.
     * @throws Throwable
     */
    public function handle(MoveFileBetweenStorageInterface $moveFileBetweenStorage): void
    {
        $moveFileBetweenStorage->move($this->file);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        if (($exception instanceof MaxAttemptsExceededException)
            && $this->currentRetryCount <= $this->maxRetries) {
            $this->delay(now()->addMinutes(random_int(0, $this->backoffFactor)));
            ++$this->currentRetryCount;

            dispatch($this);
        }
    }
}
