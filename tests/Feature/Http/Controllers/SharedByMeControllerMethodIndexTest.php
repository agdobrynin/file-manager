<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\File;
use App\Models\FileShare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SharedByMeControllerMethodIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_share_properties_and_pagination(): void
    {
        // Share by $otherUser
        $otherUser = User::factory()->create();
        FileShare::factory()
            ->count(3)
            ->afterMaking(
                fn (FileShare $fileShare) => $fileShare->file()
                    ->associate(File::factory()->isFile($otherUser)->create())
            )
            ->for(User::factory()->create(), 'forUser')
            ->createQuietly();

        // Share by $user - test it.
        $user = User::factory()->create();
        $this->actingAs($user);

        $file1 = File::factory()->isFile()->create();
        $file2 = File::factory()->isFile()->create();
        $file3 = File::factory()->isFile()->create();

        $files = collect([$file1, $file2, $file3]);

        $fileShares = FileShare::factory()
            ->count(3)
            ->afterMaking(fn (FileShare $fileShare) => $fileShare->file()->associate($files->random()))
            ->for(User::factory()->create(), 'forUser')
            ->create();

        Config::set('app.share_by_me.per_page', 2);

        $this->actingAs($user)
            ->get('/share-by-me')
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('SharedByMe')
                    ->url('/share-by-me')
                    ->where('auth.user.id', $user->id)
                // FileShareResource::class
                    ->where('files.data.0.id', $fileShares[0]->id)
                    ->where('files.data.0.name', $fileShares[0]->file->name)
                    ->where('files.data.0.disk', $fileShares[0]->file->disk->value)
                    ->where('files.data.0.path', $fileShares[0]->file->path)
                    ->where('files.data.0.parentId', $fileShares[0]->file->parent_id)
                    ->where('files.data.0.isFolder', $fileShares[0]->file->isFolder())
                    ->where('files.data.0.mime', $fileShares[0]->file->mime)
                    ->where('files.data.0.size', $fileShares[0]->file->size)
                    ->where('files.data.0.owner', $fileShares[0]->file->owner)
                    ->where('files.data.0.shareForUser', $fileShares[0]->forUser->name)
                // Pagination info
                    ->has('files.data', 2)
                    ->where('files.links.next', Config('app.url').'/share-by-me?page=2')
                    ->where('files.meta.total', 3)
            );
    }

    public function test_file_share_search_and_pagination(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        // File share with name start with 'file-'
        FileShare::factory(3)
            ->afterMaking(
                function (FileShare $fileShare): void {
                    $file = File::factory()->create(
                        ['name' => 'file-'.fake()->uuid]
                    );
                    $fileShare->file()->associate($file);
                }
            )
            ->for(User::factory()->create(), 'forUser')
            ->create();
        // Other file share
        FileShare::factory(10)
            ->afterMaking(
                fn (FileShare $fileShare) => $fileShare->file()->associate(File::factory()->create())
            )
            ->for(User::factory()->create(), 'forUser')
            ->create();

        Config::set('app.share_by_me.per_page', 2);

        $this->actingAs($user)
            ->get('/share-by-me?search=file-')
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                // Pagination info
                    ->has('files.data', 2)
                    ->where('files.links.next', Config('app.url').'/share-by-me?search=file-&page=2')
                    ->where('files.meta.total', 3)
                    ->where('files.data.0.name', fn (string $name) => str_starts_with($name, 'file-'))
                    ->where('files.data.1.name', fn (string $name) => str_starts_with($name, 'file-'))
            );

        $this->assertDatabaseCount(FileShare::class, 13);
    }

    public function test_unverified_user(): void
    {
        $user = User::factory()->unverified()->create();

        $this->followingRedirects()
            ->actingAs($user)
            ->get('/share-by-me')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Auth/VerifyEmail')
                    ->url('/verify-email')
                    ->where('auth.user.id', $user->id)
            );
    }

    public function test_user_not_auth(): void
    {
        $this->followingRedirects()->get('/share-by-me')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Auth/Login')
                    ->url('/login')
                    ->where('auth.user', null)
            );
    }
}
