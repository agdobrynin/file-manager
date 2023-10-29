<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Contracts\MoveFileBetweenStorageInterface;
use App\Jobs\MoveFileToCloud;
use App\Models\File;
use PHPUnit\Framework\TestCase;

class MoveFileToCloudTest extends TestCase
{
    public function test_handle(): void
    {
        $file = File::factory()->isFile()->make();

        $mockMoveFile = $this->createMock(MoveFileBetweenStorageInterface::class);
        $mockMoveFile->expects(self::once())
            ->method('move')
            ->with($file);

        (new MoveFileToCloud($file))->handle($mockMoveFile);
    }
}
