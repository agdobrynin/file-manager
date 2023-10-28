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

class SharedForMeControllerMethodIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user(): void
    {
        $user = User::factory()->unverified()->create();

        $this->followingRedirects()
            ->actingAs($user)
            ->get('/share-for-me')
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
        $this->followingRedirects()->get('/share-for-me')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Auth/Login')
                    ->url('/login')
                    ->where('auth.user', null)
            );
    }

    public function test_file_share_properties_and_pagination(): void
    {
        // Share by $otherUser for some users (for test display share only for me)
        $otherUser = User::factory()->create();
        FileShare::factory()
            ->count(3)
            ->afterMaking(
                fn (FileShare $fileShare) => $fileShare->file()
                    ->associate(File::factory()->isFile($otherUser)->createQuietly())
            )
            ->for(User::factory()->create(), 'forUser')
            ->createQuietly();

        // Share for me
        $userMe = User::factory()->create();
        // Share from user
        $userFrom = User::factory()->create();

        $fileShares = FileShare::factory()
            ->count(3)
            ->afterMaking(
                fn (FileShare $fileShare) => $fileShare->file()
                    ->associate(File::factory()->isFile($userFrom)->createQuietly())
            )
            ->for($userMe, 'forUser')
            ->createQuietly();

        Config::set('app.share_for_me.per_page', 2);

        $this->actingAs($userMe)
            ->get('/share-for-me')
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('SharedForMe')
                    ->url('/share-for-me')
                    ->where('auth.user.id', $userMe->id)
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
                    ->where('files.links.next', Config('app.url').'/share-for-me?page=2')
                    ->where('files.meta.total', 3)
            );
    }

    public function test_file_share_search_and_pagination(): void
    {
        // Share by $otherUser for some users (for test display share only for me)
        $otherUser = User::factory()->create();
        // Make file share with file name end with '.pdf' for other user.
        FileShare::factory()
            ->count(3)
            ->afterMaking(
                fn (FileShare $fileShare) => $fileShare->file()
                    ->associate(
                        File::factory()->for($otherUser, 'user')
                            ->for($otherUser, 'userUpdate')
                            ->create(['name' => fake()->uuid.'.pdf'])
                    )
            )
            ->for(User::factory()->create(), 'forUser')
            ->createQuietly();

        // Share for me
        $userMe = User::factory()->create();
        // Share from user
        $userFrom = User::factory()->create();
        // Share files with name like "*.pdf" <-- use it for search
        FileShare::factory()
            ->count(3)
            ->afterMaking(
                fn (FileShare $fileShare) => $fileShare->file()
                    ->associate(
                        File::factory()
                            ->for($userFrom, 'user')
                            ->for($userFrom, 'userUpdate')
                            ->create(['name' => fake()->uuid.'.pdf'])
                    )
            )
            ->for($userMe, 'forUser')
            ->createQuietly();
        // Add random files with share for me
        FileShare::factory()
            ->count(3)
            ->afterMaking(
                fn (FileShare $fileShare) => $fileShare->file()
                    ->associate(
                        File::factory()
                            ->for($userFrom, 'user')
                            ->for($userFrom, 'userUpdate')
                            ->create()
                    )
            )
            ->for($userMe, 'forUser')
            ->createQuietly();

        // All file share in database for other user, and for me.
        $this->assertDatabaseCount(FileShare::class, 9);

        Config::set('app.share_for_me.per_page', 2);

        $this->actingAs($userMe)
            ->get('/share-for-me?search=.pdf') // <-- search query string ".pdf"
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('SharedForMe')
                // Pagination info
                    ->has('files.data', 2)
                    ->where('files.meta.total', 3)
                    ->where('files.links.next', Config('app.url').'/share-for-me?search=.pdf&page=2')
                    ->where('files.data.0.name', fn (string $name) => str_ends_with($name, '.pdf'))
                    ->where('files.data.1.name', fn (string $name) => str_ends_with($name, '.pdf'))
            );
    }
}
