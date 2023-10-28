<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FileControllerMethodCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_auth_user(): void
    {
        $this->followingRedirects()->post('/file/create')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Auth/Login')
                    ->url('/login')
                    ->where('auth.user', null)
            );
    }

    public function test_not_verified_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->followingRedirects()->actingAs($user)->post('/file/create')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Auth/VerifyEmail')
                    ->url('/verify-email')
                    ->where('auth.user.id', $user->id)
            );
    }

    public function test_not_owner(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);
        $otherRoot = File::makeRootByUser($otherUser);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/file/create/'.$otherRoot->id, ['name' => 'Folder'])
            ->assertForbidden();
    }

    public static function dataValidation(): \Generator
    {
        yield 'empty input' => [
            'data' => [],
            'errors' => ['name'],
        ];

        yield 'invalid symbols \\' => [
            'data' => ['name' => '\\Folder'],
            'errors' => ['name'],
        ];

        yield 'invalid symbols /' => [
            'data' => ['name' => 'Folder/folder'],
            'errors' => ['name'],
        ];

        yield 'invalid symbols :' => [
            'data' => ['name' => 'Folder:folder'],
            'errors' => ['name'],
        ];

        yield 'invalid symbols *' => [
            'data' => ['name' => '*Folder'],
            'errors' => ['name'],
        ];

        yield 'invalid symbols ?' => [
            'data' => ['name' => 'Folder?'],
            'errors' => ['name'],
        ];

        yield 'invalid symbols >' => [
            'data' => ['name' => 'Folder>folder'],
            'errors' => ['name'],
        ];

        yield 'invalid symbols left angle bracket' => [
            'data' => ['name' => 'Folder<folder'],
            'errors' => ['name'],
        ];

        yield 'invalid symbols |' => [
            'data' => ['name' => 'Folder|folder'],
            'errors' => ['name'],
        ];

        yield 'success' => [
            'data' => ['name' => 'my folder'],
            'errors' => [],
        ];

        yield 'folder with this name already exist' => [
            'data' => ['name' => 'My folder'],
            'errors' => ['name'],
            'initDb' => fn (File $root) => $root->appendNode(File::factory()->isFolder()->make(['name' => 'My folder'])),
        ];
    }

    /**
     * @dataProvider dataValidation
     */
    public function test_request_validation(
        array $data,
        array $errors,
        \Closure $initDb = null,
    ): void {
        $user = User::factory()->create();
        $this->actingAs($user);
        $root = File::makeRootByUser($user);

        if ($initDb) {
            $initDb($root);
        }

        $response = $this->actingAs($user)->post('/file/create', $data);

        if ($errors) {
            $response->assertSessionHasErrors($errors);
        } else {
            $this->assertDatabaseHas(
                File::class,
                ['is_folder' => true, 'name' => $data['name'], 'created_by' => $user->id, 'parent_id' => $root->id]
            );
        }
    }

    public function test_create_success_sub_folder(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $root = File::makeRootByUser($user);
        $folder = File::factory()->isFolder()->make(['name' => 'Abc']);
        $root->appendNode($folder);

        $this->actingAs($user)
            ->followingRedirects()
            ->post('/file/create/'.$folder->id, ['name' => 'Abc'])
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('parentId', $folder->id)
                    ->where('ancestors.data.1.name', 'Abc')
                    ->where('ancestors.data.1.id', $folder->id)
                    ->where('files.data.0.name', 'Abc')
                    ->where('files.data.0.path', '/Abc/Abc')
                    ->where('files.data.0.parentId', $folder->id)
            );

        $this->assertDatabaseHas(
            File::class,
            ['is_folder' => true, 'name' => 'Abc', 'created_by' => $user->id, 'parent_id' => $root->id]
        );

        $this->assertCount(2, File::where(['name' => 'Abc'])->get());
    }
}
