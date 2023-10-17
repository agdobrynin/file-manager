<?php

namespace Tests\Feature\Services;

use App\Contracts\StorageByDiskTypeServiceInterface;
use App\Models\File;
use App\Models\User;
use App\Services\Exceptions\FileContentNotFoundException;
use App\Services\GetFileContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class GetFileContentTest extends TestCase
{
    use RefreshDatabase;
    public function test_get_file_content(): void
    {
        $file = File::factory()->isFile(User::factory()->create())->createQuietly();

        $mockStorage = $this->mock(
            StorageByDiskTypeServiceInterface::class,
            function (MockInterface $mock) {
                $mock->shouldReceive('resolve->filesystem->get')
                    ->andReturn('file-content-as-string');
            }
        );

        $content = (new GetFileContent($mockStorage))->getContent($file);
        $this->assertIsString($content);
    }

    public function test_get_file_content_not_found(): void
    {
        $file = File::factory()->isFile(User::factory()->create())->createQuietly();

        $mockStorage = $this->mock(
            StorageByDiskTypeServiceInterface::class,
            function (MockInterface $mock) {
                $mock->shouldReceive('resolve->filesystem->get')
                    ->andReturnNull();
            }
        );

        $this->expectException(FileContentNotFoundException::class);

        (new GetFileContent($mockStorage))->getContent($file);
    }
}
