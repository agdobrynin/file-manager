<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Contracts\StorageServiceInterface;
use App\Enums\DiskEnum;
use App\Models\File;
use App\Models\User;
use App\Services\UploadTreeFilesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Mockery\MockInterface;
use Tests\TestCase;

class UploadTreeFilesServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fail(): void
    {
        $user = User::factory()->create();
        $storage = $this->mock(StorageServiceInterface::class);

        $srv = new UploadTreeFilesService($storage);
        $parent = File::factory()->isFile($user)->createQuietly();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Parent element must be is folder');

        $srv->upload(parentFolder: $parent, files: []);
    }

    public function test_upload_service(): void
    {
        $user = User::factory()->create();
        Auth::shouldReceive('id')->andReturn($user->id);
        $root = File::makeRootByUser($user);

        $files = [
            UploadedFile::fake()->create('pdf.pdf'),
            'images' => [
                UploadedFile::fake()->image('img-1.png'),
                UploadedFile::fake()->image('img-2.png'),
                UploadedFile::fake()->image('img-3.png'),
            ],
        ];

        $storage = $this->mock(StorageServiceInterface::class, function (MockInterface $mock): void {
            $filesCount = 4; // count of files in $files variable.

            $mock->shouldReceive('upload')
                ->times($filesCount);
            $mock->shouldReceive('disk')
                ->andReturn(DiskEnum::LOCAL)
                ->times($filesCount);
        });

        $srv = new UploadTreeFilesService($storage);
        $models = $srv->upload(parentFolder: $root, files: $files);

        foreach ($models as $model) {
            $this->assertDatabaseHas(File::class, $model->toArray());
        }
        // Has directory
        $this->assertDatabaseHas(
            File::class,
            ['name' => 'images', 'is_folder' => true, 'parent_id' => $root->id]
        );
        $this->assertDatabaseHas(
            File::class,
            ['name' => 'pdf.pdf', 'disk' => 'local', 'path' => '/pdf.pdf', 'is_folder' => false, 'parent_id' => $root->id]
        );
        //Images in subdirectory
        $root->children->firstWhere(fn (File $file) => $file->isFolder())
            ->children
            ->each(function (File $file): void {
                $this->assertStringStartsWith('/images/img-', $file->path);
                $this->assertEquals(DiskEnum::LOCAL, $file->disk);
            });
    }
}
