<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\File;
use App\Models\FileShare;
use App\Models\User;
use Closure;
use Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
