<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\File;
use App\Models\FileShare;
use App\Models\User;
use Closure;
use Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SharedForMeControllerMethodDownloadTest extends TestCase
{
    use RefreshDatabase;

    public static function dataMethodAllowed(): Generator
    {
        yield 'method post' => ['post'];
        yield 'method delete' => ['delete'];
        yield 'method patch' => ['patch'];
        yield 'method put' => ['put'];
    }

    public static function dataValidation(): Generator
    {
        yield 'empty params' => [
            'data' => [],
            'errors' => ['ids'],
            'noErrors' => ['all']
        ];

        yield 'param "all = false" but not set param "ids"' => [
            'data' => ['all' => false],
            'errors' => ['ids'],
            'noErrors' => ['all']
        ];

        yield 'param "all = false" but param "ids" is string' => [
            'data' => ['all' => false, 'ids' => ''],
            'errors' => ['ids'],
            'noErrors' => ['all']
        ];

        yield 'param "all = false" but param "ids" is empty' => [
            'data' => ['all' => false, 'ids' => []],
            'errors' => ['ids'],
            'noErrors' => ['all']
        ];

        yield 'param "all = false" param "ids"' => [
            'data' => ['all' => false, 'ids' => [1, 2, 3]],
            'errors' => ['ids'],
            'noErrors' => ['all']
        ];

        yield 'param "ids" not owner' => [
            'data' => function () {
                return [
                    'all' => false,
                    'ids' => FileShare::factory()
                        ->afterMaking(
                            fn(FileShare $fileShare) => $fileShare->file()
                                ->associate(
                                    File::factory()->isFile(User::factory()->create())
                                        ->createQuietly()
                                )
                        )
                        ->for(User::factory()->create(), 'forUser')
                        ->create()
                        ->pluck('id')
                        ->toArray()
                ];
            },
            'errors' => ['ids'],
            'noErrors' => ['all']
        ];
    }

    /**
     * @dataProvider dataMethodAllowed
     */
    public function test_method_allowed(string $method): void
    {
        $this->actingAs(User::factory()->create())
            ->{$method}('/share-for-me/download')
            ->assertMethodNotAllowed();
    }

    /**
     * @dataProvider dataValidation
     */
    public function test_validation(array|Closure $data, array $errors, array $noErrors): void
    {
        $user = User::factory()->create();

        if ($data instanceof Closure) {
            $data = $data();
        }

        $response = $this->actingAs($user)
            ->get('/share-for-me/download?' . http_build_query($data));

        if ($errors) {
            $response->assertSessionHasErrors($errors);
        }

        if ($noErrors) {
            $response->assertSessionDoesntHaveErrors($noErrors);
        }
    }

    public static function dataDownloadWithErrors(): Generator
    {
        yield 'file can not load from store' => [
            fn() => FileShare::factory()
                ->afterMaking(
                    fn(FileShare $fileShare) => $fileShare->file()
                        ->associate(File::factory()->isFile()->create())
                )
                ->for(User::factory()->create(), 'forUser')
                ->create()
                ->pluck('id')
                ->toArray()
        ];

        yield 'empty folder' => [
            fn() => FileShare::factory()
                ->afterMaking(
                    fn(FileShare $fileShare) => $fileShare->file()
                        ->associate(File::factory()->isFolder()->create())
                )
                ->for(User::factory()->create(), 'forUser')
                ->create()
                ->pluck('id')
                ->toArray()
        ];
    }

    /**
     * @dataProvider dataDownloadWithErrors
     */
    public function test_download_with_errors(Closure $initShareId): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $queryString = http_build_query(['ids' => $initShareId()]);

        $this->actingAs($user)
            ->get('/share-for-me/download?all=false&' . $queryString)
            ->assertStatus(400)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_download_single_file(): void
    {
        $userMe = User::factory()->create();
        $userFrom = User::factory()->create();

        /** @var FileShare $shareFile */
        $shareFile = FileShare::factory()
            ->afterMaking(
                fn(FileShare $fileShare) => $fileShare->file()
                    ->associate(
                        File::factory()->isFile()
                            ->for($userFrom, 'user')
                            ->for($userFrom, 'userUpdate')
                            ->createQuietly()
                    )
            )
            ->for($userMe, 'forUser')
            ->create();
        // make store for file
        /** @var File $file */
        $file = $shareFile->file;
        $storage = Storage::fake($file->disk->value);
        $storage->put($file->storage_path, 'content');

        $this->actingAs($userMe)
            ->get('/share-for-me/download?all=false&ids[]=' . $shareFile->id)
            ->assertOk()
            ->assertHeader(
                'content-disposition',
                'attachment; filename=' . $file->name
            );
    }

    public function test_download_folder_with_files(): void
    {
        $userMe = User::factory()->create();
        $userFrom = User::factory()->create();

        $this->actingAs($userFrom);
        /** @var FileShare $shareFolder for folder with name "My folder" */
        $shareFolder = FileShare::factory()
            ->afterMaking(
                fn(FileShare $fileShare) => $fileShare->file()
                    ->associate(
                        File::factory()
                            ->isFolder()
                            ->create(['name' => 'Documents'])
                    )
            )
            ->for($userMe, 'forUser')
            ->create();
        // Add files to share Folder
        /** @var File $folder */
        $folder = $shareFolder->file;

        $file1 = File::factory()
            ->isFile()
            ->create();
        $folder->appendNode($file1);

        $file2 = File::factory()
            ->isFile()
            ->create();
        $folder->appendNode($file2);
        // make storage
        $storage = Storage::fake($file1->disk->value);
        foreach ([$file1, $file2] as $file) {
            $storage->put($file->storage_path, 'content');
        }

        $this->actingAs($userMe)
            ->get('/share-for-me/download?all=false&ids[]=' . $shareFolder->id)
            ->assertOk()
            ->assertHeader(
                'content-disposition',
                'attachment; filename=Documents.zip'
            );
    }
}
