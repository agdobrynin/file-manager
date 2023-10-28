<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Contracts\StorageCloudServiceInterface;
use App\Contracts\StorageLocalServiceInterface;
use App\Contracts\StorageServiceInterface;
use App\Enums\DiskEnum;
use App\Models\File;
use App\Models\User;
use App\Services\Exceptions\MoveFileBetweenStorageException;
use App\Services\MoveFileBetweenStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MoveFileBetweenStorageTest extends TestCase
{
    use RefreshDatabase;

    public static function data(): \Generator
    {
        yield 'success move' => [
            'copySuccess' => true,
        ];

        yield 'fail move' => [
            'copySuccess' => false,
            'exception' => MoveFileBetweenStorageException::class,
        ];
    }

    /** @dataProvider data */
    public function test_service(bool $copySuccess, string $exception = null): void
    {
        $user = User::factory()->create();
        $file = File::factory()->isFile($user)->createQuietly([
            'disk' => DiskEnum::LOCAL,
        ]);
        $file->refresh();
        $this->assertEquals(DiskEnum::LOCAL, $file->disk);

        if ($exception) {
            $this->expectException($exception);
        }

        $storageTo = $this->mock(
            StorageLocalServiceInterface::class,
            function (MockInterface $mock) use ($file, $copySuccess, $exception): void {
                $mock->shouldReceive('filesystem->put')
                    ->with($file->storage_path, '')
                    // success copy to cloud
                    ->andReturn($copySuccess)
                    ->once();

                $mock->shouldReceive('disk')
                    ->andReturn(DiskEnum::CLOUD)
                    ->times($exception ? 0 : 1);
            }
        );

        $storageFrom = $this->mock(
            StorageCloudServiceInterface::class,
            function (MockInterface $mock) use ($file, $exception): void {
                // Get content of file
                $mock->shouldReceive('filesystem->get')
                    ->with($file->storage_path)
                    ->once();
                // Delete from local storage
                $mock->shouldReceive('filesystem->delete')
                    ->with($file->storage_path)
                    ->times($exception ? 0 : 1);
                // Try to delete all empty directory
                $mock->shouldReceive('filesystem->allDirectories')
                    ->andReturn(['dir1/sub1', 'dir1'])
                    ->times($exception ? 0 : 1);

                $mock->shouldReceive('filesystem->allFiles')
                    ->with('dir1/sub1')
                    ->andReturn([])
                    ->times($exception ? 0 : 1);

                $mock->shouldReceive('filesystem->allFiles')
                    ->with('dir1')
                    ->andReturn([])
                    ->times($exception ? 0 : 1);

                $mock->shouldReceive('filesystem->deleteDirectory')
                    ->times($exception ? 0 : 2);
            }
        );

        $srv = new MoveFileBetweenStorage($storageFrom, $storageTo);

        $srv->move($file);

        $this->assertInstanceOf(StorageServiceInterface::class, $srv->storageServiceTo());
        $this->assertInstanceOf(StorageServiceInterface::class, $srv->storageServiceFrom());

        $this->assertEquals(DiskEnum::CLOUD, $file->disk);
        $this->assertDatabaseHas(File::class, $file->toArray());
    }
}
