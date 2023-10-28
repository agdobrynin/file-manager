<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FileTrashControllerMethodIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_trash(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $root = File::makeRootByUser($user);
        File::factory(2)
            ->afterMaking(fn (File $file) => $root->appendNode($file))
            ->isFile()->make();

        $this->actingAs($user)
            ->get('/trash')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('MyTrash')
                    ->url('/trash')
                    ->has('auth.user.name')
                    ->has('files.data', 0)
                //pagination nullable
                    ->where('files.links.next', null)
                    ->where('files.meta.total', 0)
            );
    }

    public function test_files_in_trash_other_user_and_current_user_has_empty_trash(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);
        $otherRoot = File::makeRootByUser($otherUser);

        File::factory(5)
            ->afterMaking(fn (File $file) => $otherRoot->appendNode($file))
            ->isFile()->make(['deleted_at' => now()]);

        $this->actingAs(User::factory()->create())
            ->get('/trash')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->has('files.data', 0)
                    ->where('files.meta.total', 0)
            );
    }

    public function test_search_in_trash_with_pagination(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $root = File::makeRootByUser($user);

        File::factory(5)
            ->afterMaking(fn (File $file) => $root->appendNode($file))
            ->isFile()->make(['deleted_at' => now()]);
        // Files has extension .png
        File::factory(3)
            ->afterMaking(fn (File $file) => $root->appendNode($file))
            ->isFile()->make(['deleted_at' => now(), 'name' => fake()->title.'.png']);

        Config::set('app.my_trash.per_page', 2);

        $this->actingAs($user)
            ->get('/trash?search=.png')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                // per page 2 items
                    ->has('files.data', 2)
                //pagination nullable
                    ->where('files.links.next', Config::get('app.url').'/trash?search=.png&page=2')
                    ->where('files.meta.total', 3)
            );
    }

    public function test_search_validation(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/trash?search[]=files')
            ->assertRedirect()
            ->assertSessionHasErrors('search');
    }

    public function test_trash_has_files_with_pagination_and_check_file_in_trash_resource(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $root = File::makeRootByUser($user);
        // total
        /** @var Collection $files */
        $files = File::factory(10)
            ->afterMaking(fn (File $file) => $root->appendNode($file))
            ->isFile()->make();
        // Mark as trash 3 file
        $deletedFiles = $files->slice(0, 3)->each(fn (File $file) => $file->deleteQuietly());

        Config::set('app.my_trash.per_page', 2);

        $this->actingAs($user)
            ->get('/trash')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('MyTrash')
                    ->url('/trash')
                // per page 2 items
                    ->has('files.data', 2)
                //pagination nullable
                    ->where('files.links.next', Config::get('app.url').'/trash?page=2')
                    ->where('files.meta.total', 3)
                // Check FileInTrashResource
                    ->whereType('files.data.0.id', 'integer')
                    ->whereType('files.data.0.name', 'string')
                    ->whereType('files.data.0.disk', 'string')
                    ->whereType('files.data.0.path', 'string')
                    ->whereType('files.data.0.parentId', 'integer')
                    ->whereType('files.data.0.isFolder', 'boolean')
                    ->whereType('files.data.0.size', 'integer')
                    ->where('files.data.0.deletedAt', fn (string $val) => '' !== $val)
            );
    }

    public function test_user_not_auth(): void
    {
        $this->followingRedirects()
            ->get('/trash')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Auth/Login')
                    ->url('/login')
                    ->where('auth.user', null)
            );
    }

    public function test_user_not_verified(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->followingRedirects()
            ->get('/trash')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Auth/VerifyEmail')
                    ->url('/verify-email')
                    ->has('auth.user.name')
                    ->where('auth.user.email', $user->email)
                    ->where('auth.user.email_verified_at', null)
            );
    }

    public static function dataMethodAllowed(): \Generator
    {
        yield 'method post' => ['post'];
        yield 'method delete' => ['delete'];
        yield 'method patch' => ['patch'];
        yield 'method put' => ['put'];
    }

    /**
     * @dataProvider dataMethodAllowed
     */
    public function test_method_allowed(string $method): void
    {
        $this->actingAs(User::factory()->create())
            ->{$method}('/trash')
            ->assertMethodNotAllowed();
    }
}
