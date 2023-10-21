<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FileControllerMethodDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_folder_not_found(): void
    {
        $user = User::factory()->create();
        $data = [];

        $this->actingAs($user)->delete('/file/destroy/1000000', $data)
            ->assertNotFound();
    }

    public function test_user_not_auth(): void
    {
        $this->followingRedirects()
            ->delete('/file/destroy')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('Auth/Login')
                ->url('/login')
                ->where('auth.user', null)
            );
    }

    public function test_user_has_not_verified_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->followingRedirects()
            ->actingAs($user)
            ->delete('/file/destroy')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('Auth/VerifyEmail')
                ->url('/verify-email')
                ->where('auth.user.id', $user->id)
            );
    }

    public static function dataMethodNotAllowed(): \Generator
    {
        yield 'method get' => ['get'];
        yield 'method post' => ['post'];
        yield 'method patch' => ['patch'];
    }

    /**
     * @dataProvider dataMethodNotAllowed
     */
    public function test_method_not_allowed(string $method): void
    {
        $user = User::factory()->create();
        $data = [];

        $this->actingAs($user)->{$method}('/file/destroy/1000000', $data)
            ->assertMethodNotAllowed();
    }

    public static function dataForValidation(): \Generator
    {
        yield 'all is undefined and ids not set' => [
            'data' => [],
            'errors' => ['ids' => 'The ids field is required when all is false.'],
            'noErrors' => ['all'],
        ];

        yield 'all is set false and ids not set' => [
            'data' => [
                'all' => false,
                'ids' => 1,
            ],
            'errors' => ['ids' => 'The ids field must be an array.'],
            'noErrors' => ['all'],
        ];

        yield 'all is set "true" parent param in route not set' => [
            'data' => [
                'all' => true,
            ],
            'errors' => ['all' => 'When parameter "all" is true route parameter "parentFolder" is required.'],
            'noErrors' => ['ids'],
        ];

        yield 'all is set "false" ids set is wrong' => [
            'data' => [
                'all' => false,
                'ids' => [10, 11, 12],
            ],
            'errors' => ['ids' => 'Some file IDs are not valid.'],
            'noErrors' => ['all'],
        ];

        yield 'all is set "false" ids set from other owner' => [
            'data' => [
                'all' => false,
                'ids' => function () {
                    $u = User::factory()->create();

                    return File::factory(3)
                        ->isFile($u)
                        ->createQuietly()
                        ->pluck('id')
                        ->toArray();
                },
            ],
            'errors' => ['ids' => 'Some file IDs are not valid.'],
            'noErrors' => ['all'],
        ];
    }

    /**
     * @dataProvider dataForValidation
     */
    public function test_validation(array $data, array $errors, array $noErrors): void
    {
        $user = User::factory()->create();

        if (($data['ids'] ?? null) instanceof \Closure) {
            $data['ids'] = $data['ids']();
            foreach ($data['ids'] as $id) {
                $this->assertDatabaseHas(File::class, ['id' => $id]);
            }
        }

        $this->actingAs($user)->delete('/file/destroy', $data)
            ->assertSessionHasErrors($errors)
            ->assertSessionDoesntHaveErrors($noErrors);
    }

    public function test_delete_all_in_root_success(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $root = File::makeRootByUser($user);
        $files = File::factory(2)
            ->afterMaking(fn(File $file) => $root->appendNode($file))
            ->make();
        $dataset = $files->toArray();

        foreach ($dataset as $item) {
            $this->assertDatabaseHas(File::class, $item);
        }

        $this->actingAs($user)->delete('/file/destroy/' . $root->id, ['all' => true])
            ->assertRedirect('/file/' . $root->id)
            ->assertSessionDoesntHaveErrors(['all', 'ids']);

        foreach ($dataset as $item) {
            $this->assertSoftDeleted(File::class, ['id' => $item['id']]);
        }
    }

    public function test_delete_by_ids_success(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $root = File::makeRootByUser($user);
        $files = File::factory(2)
            ->afterMaking(fn(File $file) => $root->appendNode($file))
            ->make();

        $dataset = $files->toArray();

        foreach ($dataset as $item) {
            $this->assertDatabaseHas(File::class, $item);
        }
        // destroy by ids item #2 from dataset
        $this->actingAs($user)->delete(
            '/file/destroy/' . $root->id,
            ['all' => false, 'ids' => [$dataset[1]['id']]]
        )
            ->assertRedirect('/file/' . $root->id)
            ->assertSessionDoesntHaveErrors(['all', 'ids']);

        $this->assertNotSoftDeleted(File::class, ['id' => $root->id]);
        $this->assertNotSoftDeleted(File::class, ['id' => $dataset[0]['id']]);

        $this->assertSoftDeleted(File::class, ['id' => $dataset[1]['id']]);
    }

    public function test_destroy_with_parent_folder_not_owner(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $rootOther = File::makeRootByUser($otherUser);
        $filesOther = File::factory(2)
            ->afterMaking(fn(File $file) => $rootOther->appendNode($file))
            ->make();

        $user = User::factory()->create();
        $this->actingAs($user);
        File::makeRootByUser($user);

        $this->actingAs($user)->delete(
            '/file/destroy/' . $rootOther->id,
            ['all' => true]
        )->assertForbidden();
    }
}
