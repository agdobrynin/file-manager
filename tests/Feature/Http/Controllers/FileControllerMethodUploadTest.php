<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
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
                'relativePaths' => []
            ],
            'errors' => ['relativePaths'],
            'noErrors' => ['files'],
        ];

        yield 'data relative path is string' => [
            'data' => [
                'files' => [UploadedFile::fake()->image('img.png')],
                'relativePaths' => '/folder/img.png'
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
                'relativePaths' => ['/folder/img.png', 'folder1/img1.png']
            ],
            'errors' => ['relativePaths'],
            'noErrors' => ['files'],
            'maxFiles' => 1,
        ];

        yield 'param "files" in request is not file' => [
            'data' => [
                'files' => ['abc'],
                'relativePaths' => ['/folder/img.png']
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
        ?int  $maxFiles = null,
    ): void
    {
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
}
