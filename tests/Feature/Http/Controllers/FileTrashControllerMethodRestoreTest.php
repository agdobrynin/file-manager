<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileTrashControllerMethodRestoreTest extends TestCase
{
    use RefreshDatabase;

    public static function dataMethodNotAllowed(): \Generator
    {
        yield 'method get' => ['get'];
        yield 'method delete' => ['delete'];
        yield 'method patch' => ['patch'];
        yield 'method put' => ['put'];
    }

    /**
     * @dataProvider dataMethodNotAllowed
     */
    public function test_method_not_allowed(string $method): void
    {
        $this->actingAs(User::factory()->create())
            ->{$method}('/trash/restore')
            ->assertMethodNotAllowed();
    }

    public static function dataForValidation(): \Generator
    {
        yield 'all is undefined and ids not set' => [
            'data' => [],
            'errors' => ['ids'],
            'noErrors' => ['all'],
        ];

        yield 'all is set false and ids not set' => [
            'data' => [
                'all' => false,
                'ids' => 1,
            ],
            'errors' => ['ids'],
            'noErrors' => ['all'],
        ];

        yield 'all is set "true" parent param in route not set' => [
            'data' => [
                'all' => true,
            ],
            'errors' => [],
            'noErrors' => ['all', 'ids'],
        ];

        yield 'all is set "false" ids set is wrong' => [
            'data' => [
                'all' => false,
                'ids' => [10, 11, 12],
            ],
            'errors' => ['ids'],
            'noErrors' => ['all'],
        ];

        yield 'all is set "false" ids set from other owner' => [
            'data' => [
                'all' => false,
                'ids' => function () {
                    $u = User::factory()->create();

                    return File::factory(3)
                        ->state(['deleted_at' => now()])
                        ->isFile($u)
                        ->createQuietly()
                        ->pluck('id')
                        ->toArray();
                },
            ],
            'errors' => ['ids'],
            'noErrors' => ['all'],
        ];
    }

    /**
     * @dataProvider dataForValidation
     */
    public function test_validation(array $data, array $errors, array $noErrors): void
    {
        $user = User::factory()->create();

        if (isset($data['ids']) && $data['ids'] instanceof \Closure) {
            $data['ids'] = $data['ids']();
        }

        $response = $this->actingAs($user)
            ->from('/trash')
            ->post('/trash/restore', $data)
            ->assertRedirect('/trash');

        if ($errors) {
            $response->assertSessionHasErrors($errors);
        }

        $response->assertSessionDoesntHaveErrors($noErrors);
    }

    public function test_restore_all_success(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $files = collect(
            [
                File::factory()->isFile()->deleted()->create(),
                File::factory()->isFile()->deleted()->create(),
                File::factory()->isFile()->deleted()->create(),
            ]
        );

        $ids = $files->pluck('id')->toArray();

        foreach ($ids as $id) {
            $this->assertSoftDeleted(File::class, ['id' => $id]);
        }

        $this->actingAs($user)
            ->from('/trash')
            ->post('/trash/restore', ['all' => true])
            ->assertRedirect('/trash')
            ->assertSessionMissing('warning')
            ->assertSessionHas('success');

        foreach ($ids as $id) {
            $this->assertNotSoftDeleted(File::class, ['id' => $id]);
        }
    }

    public function test_restore_file_when_file_already_exist(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        /** @var File $fileInTrash file move to trash */
        $fileInTrash = File::factory()->isFile()->deleted()->create();
        $this->assertSoftDeleted(File::class, ['id' => $fileInTrash->id]);
        /** @var File $file file exist in main list */
        $file = $fileInTrash->replicate(['deleted_at']);
        $file->save();

        $this->actingAs($user)
            ->from('/trash')
            ->post('/trash/restore', ['ids' => [$fileInTrash->id]])
            ->assertRedirect('/trash')
            ->assertSessionHas(['warning', 'success']);

        $this->assertSoftDeleted(File::class, ['id' => $fileInTrash->id]);
    }

    public function test_restore_file(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        /** @var File $fileInTrash file move to trash */
        $fileInTrash = File::factory()->isFile()->deleted()->create();
        $this->assertSoftDeleted(File::class, ['id' => $fileInTrash->id]);

        $this->actingAs($user)
            ->from('/trash')
            ->post('/trash/restore', ['ids' => [$fileInTrash->id]])
            ->assertRedirect('/trash')
            ->assertSessionHas('success')
            ->assertSessionMissing('warning');

        $this->assertNotSoftDeleted(File::class, ['id' => $fileInTrash->id]);
    }
}
