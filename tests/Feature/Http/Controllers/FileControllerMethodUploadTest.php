<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Jobs\MoveFileToCloud;
use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FileControllerMethodUploadTest extends TestCase
{
    use RefreshDatabase;

    public static function dataValidation(): \Generator
    {
        yield 'empty data' => [
            'data' => [],
            'errors' => ['files'],
            'noErrors' => ['relativePaths'],
        ];

        yield 'data file not array' => [
            'data' => [
                'files' => UploadedFile::fake()->image('img.png'),
                'relativePaths' => ['img.png'],
            ],
            'errors' => ['files'],
            'noErrors' => ['relativePaths'],
        ];

        yield 'data relative path is empty' => [
            'data' => [
                'files' => [UploadedFile::fake()->image('img.png')],
                'relativePaths' => [],
            ],
            'errors' => ['relativePaths'],
            'noErrors' => ['files'],
        ];

        yield 'data relative path is string' => [
            'data' => [
                'files' => [UploadedFile::fake()->image('img.png')],
                'relativePaths' => '/folder/img.png',
            ],
            'errors' => ['relativePaths'],
            'noErrors' => ['files'],
        ];

        yield 'maximum files' => [
            'data' => [
                'files' => [
                    UploadedFile::fake()->image('img.png'),
                    UploadedFile::fake()->image('img1.png'),
                ],
                'relativePaths' => ['/folder/img.png', 'folder1/img1.png'],
            ],
            'errors' => ['relativePaths'],
            'noErrors' => ['files'],
            'maxFiles' => 1,
        ];

        yield 'param "files" in request is not file' => [
            'data' => [
                'files' => ['abc'],
                'relativePaths' => ['/folder/img.png'],
            ],
            'errors' => ['files.0'],
            'noErrors' => ['relativePaths'],
        ];
    }

    /**
     * @dataProvider dataValidation
     */
    public function test_validation(
        array $data,
        array $errors,
        array $noErrors,
        int $maxFiles = null,
    ): void {
        $user = User::factory()->create();
        $this->actingAs($user);
        File::makeRootByUser($user);

        if ($maxFiles) {
            Config::set('upload_files.upload.max_files', $maxFiles);
        }

        $response = $this->actingAs($user)->post('/file/upload', $data)
            ->assertRedirect();

        if ($errors) {
            $response->assertSessionHasErrors($errors);
        }

        if ($noErrors) {
            $response->assertSessionDoesntHaveErrors($noErrors);
        }
    }

    public function test_parent_folder_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/file/upload/1000000')
            ->assertNotFound();
    }

    public function test_user_have_not_root(): void
    {
        $user = User::factory()->create();
        $data = [
            'files' => [UploadedFile::fake()->image('img.png')],
            'relativePaths' => ['/folder/img.png'],
        ];

        $this->actingAs($user)->post('/file/upload', $data)
            ->assertNotFound();
    }

    public function test_exist_folder_and_exist_file(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $root = File::makeRootByUser($user);
        $folder = File::factory()->isFolder()->create(['name' => 'folder']);
        $file = File::factory()->isFile()->create(['name' => 'img.png']);
        $root->appendNode($folder);
        $root->appendNode($file);

        $data = [
            'files' => [
                UploadedFile::fake()->image('img.png'),
                UploadedFile::fake()->image('img.png'),
                UploadedFile::fake()->image('avatar.jpg'),
            ],
            'relativePaths' => [
                '/folder/img.png',
                '/img.png',
                '/avatar.jpg',
            ],
        ];

        $this->actingAs($user)->post('/file/upload/'.$root->id, $data)
            ->assertRedirect()
            ->assertSessionHasErrors(['folder.0' => 'Folder "folder" already exist'])
            ->assertSessionHasErrors(['file.0' => 'File "img.png" already exist'])
            ->assertSessionDoesntHaveErrors(['file.1']);
    }

    public function test_upload_to_other_user(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);
        $otherRoot = File::makeRootByUser($otherUser);

        $user = User::factory()->create();
        $this->actingAs($user);
        File::makeRootByUser($otherUser);

        $data = [
            'files' => [UploadedFile::fake()->image('img.png')],
            'relativePaths' => ['/img.png'],
        ];

        $this->actingAs($user)->post('/file/upload/'.$otherRoot->id, $data)
            ->assertForbidden();
    }

    public function test_success(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $root = File::makeRootByUser($user);

        $data = [
            'files' => [
                UploadedFile::fake()->image('img.png'),
                UploadedFile::fake()->image('img.png'),
                UploadedFile::fake()->image('avatar.jpg'),
            ],
            'relativePaths' => [
                '/My folder🎈/img.png',
                '/img.png',
                '/avatar.jpg',
            ],
        ];

        Queue::fake([MoveFileToCloud::class]);

        $this->actingAs($user)->post('/file/upload', $data)
            ->assertRedirect('/file/'.$root->id)
            ->assertSessionHas('success')
            ->assertSessionMissing('error');

        Queue::assertPushedOn('upload', MoveFileToCloud::class);
        Queue::assertPushed(MoveFileToCloud::class, 3);

        $this->assertDatabaseHas(
            File::class,
            ['is_folder' => true, 'name' => 'My folder🎈', 'path' => '/My folder🎈', 'parent_id' => $root->id]
        );
        $this->assertDatabaseHas(
            File::class,
            ['is_folder' => false, 'name' => 'img.png', 'path' => '/img.png', 'parent_id' => $root->id]
        );
        // File in sub folder "My folder🎈"
        $this->assertDatabaseHas(
            File::class,
            ['is_folder' => false, 'name' => 'img.png', 'path' => '/My folder🎈/img.png']
        );
    }

    public function test_has_throw_in_controller(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $root = File::makeRootByUser($user);

        $data = [
            'files' => [
                UploadedFile::fake()->image('img.png'),
                UploadedFile::fake()->image('img2.png'),
            ],
            'relativePaths' => [
                '/folder/img.png',
            ],
        ];

        Queue::fake([MoveFileToCloud::class]);

        $this->actingAs($user)->post('/file/upload', $data)
            ->assertRedirect('/file/'.$root->id)
            ->assertSessionHas('error')
            ->assertSessionMissing('success');

        Queue::assertPushed(MoveFileToCloud::class, 0);

        $this->assertDatabaseMissing(
            File::class,
            ['is_folder' => false, 'name' => 'img.png', 'path' => '/folder/img.png']
        );
        $this->assertDatabaseMissing(
            File::class,
            ['is_folder' => false, 'name' => 'img2.png']
        );
    }
}
