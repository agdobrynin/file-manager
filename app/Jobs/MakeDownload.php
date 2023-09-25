<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Models\File;
use App\Services\MakeDownloadFiles;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MakeDownload implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param Collection<File> $files
     */
    public function __construct(private readonly Collection $files)
    {
        $this->onQueue('zip');
    }

    /**
     * Execute the job.
     */
    public function handle(MakeDownloadFiles $downloadFiles): void
    {
        $dto = $downloadFiles->handle($this->files);
    }
}
