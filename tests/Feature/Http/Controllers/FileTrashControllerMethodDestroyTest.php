<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\DiskEnum;
use App\Jobs\DeleteFileFromStorageJob;
use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * DELETE trash/destroy › FileTrashController@destroy
 */
class FileTrashControllerMethodDestroyTest extends TestCase
{
    use RefreshDatabase;

    public static function dataMethodNotAllowed(): \Generator
    {
        yield 'method get' => ['get'];
        yield 'method post' => ['post'];
        yield 'method patch' => ['patch'];
        yield 'method put' => ['put'];
    }

    /**
     * @dataProvider dataMethodNotAllowed
     */
    public function test_method_not_allowed(string $method): void
    {
        $this->actingAs(User::factory()->create())
            ->{$method}('/trash/destroy')
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

                    return collect([
                        File::factory()->deleted()->isFile($u)->createQuietly(),
                        File::factory()->deleted()->isFile($u)->createQuietly(),
                        File::factory()->deleted()->isFile($u)->createQuietly(),
                    ])
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
            ->delete('/trash/destroy', $data)
            ->assertRedirect('/trash');

        if ($errors) {
            $response->assertSessionHasErrors($errors);
        }

        $response->assertSessionDoesntHaveErrors($noErrors);
    }

    public function test_destroy_all_success(): void
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
        $storagePaths = $files->pluck('storage_path')->toArray();

        foreach ($ids as $id) {
            $this->assertSoftDeleted(File::class, ['id' => $id]);
        }

        Queue::fake([DeleteFileFromStorageJob::class]);

        $this->actingAs($user)
            ->from('/trash')
            ->delete('/trash/destroy', ['all' => true])
            ->assertRedirect('/trash')
            ->assertSessionHas('success');

        Queue::assertPushed(DeleteFileFromStorageJob::class, 3);
        Queue::assertPushed(
            DeleteFileFromStorageJob::class,
            static function (DeleteFileFromStorageJob $job) use ($storagePaths) {
                return \in_array($job->dto->fileStoragePath, $storagePaths, true)
                    && $job->dto->disk instanceof DiskEnum;
            }
        );

        foreach ($ids as $id) {
            $this->assertDatabaseMissing(File::class, ['id' => $id]);
        }
    }

    public function test_destroy_by_ids_success(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $file1 = File::factory()->isFile()->deleted()->create();
        $file2 = File::factory()->isFile()->deleted()->create();
        $file3 = File::factory()->isFile()->deleted()->create();

        $this->assertSoftDeleted(File::class, $file1->toArray());
        $this->assertSoftDeleted(File::class, $file2->toArray());
        $this->assertSoftDeleted(File::class, $file3->toArray());

        Queue::fake([DeleteFileFromStorageJob::class]);

        $this->actingAs($user)
            ->from('/trash')
            ->delete('/trash/destroy', ['ids' => [$file2->id]])
            ->assertRedirect('/trash')
            ->assertSessionHas('success');

        Queue::assertPushed(DeleteFileFromStorageJob::class, 1);
        Queue::assertPushed(
            DeleteFileFromStorageJob::class,
            static function (DeleteFileFromStorageJob $job) use ($file2) {
                return $job->dto->fileStoragePath === $file2->storage_path
                    && $job->dto->disk === $file2->disk;
            }
        );

        $this->assertDatabaseMissing(File::class, ['id' => $file2->id]);
        $this->assertSoftDeleted(File::class, ['id' => $file1->id]);
        $this->assertSoftDeleted(File::class, ['id' => $file3->id]);
    }
}
