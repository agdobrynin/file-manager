<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\File;
use App\Models\FileShare;
use App\Models\User;
use Closure;
use Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SharedByMeControllerMethodUnshareTest extends TestCase
{
    use RefreshDatabase;

    public static function dataMethodAllowed(): Generator
    {
        yield 'method post' => ['post'];
        yield 'method get' => ['get'];
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
            ->{$method}('/share-by-me/unshare')
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
            ->delete('/share-by-me/unshare', $data);

        if ($errors) {
            $response->assertSessionHasErrors($errors);
        }

        if ($noErrors) {
            $response->assertSessionDoesntHaveErrors($noErrors);
        }
    }

    public function test_unshare_all(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $files = collect();
        /** @var Collection<FileShare> $fileShares */
        $fileShares = FileShare::factory(2)
            ->afterMaking(
                fn(FileShare $fileShare) => $fileShare->file()
                    ->associate(
                        tap(
                            File::factory()->isFile()->create(),
                            static fn(File $file) => $files->add($file)
                        )
                    )
            )
            ->for(User::factory()->create(), 'forUser')
            ->create();

        foreach ($fileShares as $fileShare) {
            $this->assertDatabaseHas(FileShare::class, $fileShare->withoutRelations()->toArray());
        }

        $data = ['all' => true];

        $this->actingAs($user)
            ->from('/share-by-me')
            ->delete('/share-by-me/unshare', $data)
            ->assertRedirect('/share-by-me')
            ->assertSessionHasNoErrors();

        foreach ($fileShares as $fileShare) {
            $this->assertDatabaseMissing(FileShare::class, $fileShare->withoutRelations()->toArray());
        }

        foreach ($files as $file) {
            $this->assertCount(0, $file->fileShare);
        }
    }

    public function test_unshare_single_file(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $files = collect();
        /** @var Collection<FileShare> $fileShares */
        $fileShares = FileShare::factory(2)
            ->afterMaking(
                fn(FileShare $fileShare) => $fileShare->file()
                    ->associate(
                        tap(
                            File::factory()->isFile()->create(),
                            static fn(File $file) => $files->add($file)
                        )
                    )
            )
            ->for(User::factory()->create(), 'forUser')
            ->create();

        foreach ($fileShares as $fileShare) {
            $this->assertDatabaseHas(FileShare::class, $fileShare->withoutRelations()->toArray());
        }

        $this->actingAs($user)
            ->from('/share-by-me')
            ->delete('/share-by-me/unshare', ['ids' => [$fileShares[0]->id]])
            ->assertRedirect('/share-by-me')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing(FileShare::class, $fileShares[0]->withoutRelations()->toArray());
        $this->assertDatabaseHas(FileShare::class, $fileShares[1]->withoutRelations()->toArray());

        $this->assertCount(0, $files[0]->fileShare);
        $this->assertCount(1, $files[1]->fileShare);
    }
}
