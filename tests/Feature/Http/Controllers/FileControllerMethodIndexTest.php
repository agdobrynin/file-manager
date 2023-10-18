<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\File;
use App\Models\User;
use App\VO\FileFolderVO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FileControllerMethodIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_files_list_with_pagination(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $root = File::makeRootByUser($user);
        // make files
        collect(['f-1-1.png', 'f-1-2.png', 'f-1-3.jpg'])->each(function (string $name) use ($root) {
            File::factory()
                ->afterMaking(fn(File $file) => $root->appendNode($file))
                ->isFile()->make(['name' => $name]);
        });
        $folder = File::create((new FileFolderVO('Folder1'))->toArray(), $root);
        collect(['f-2-1.png', 'f-2-2.png'])->each(function (string $name) use ($folder) {
            File::factory()
                ->afterMaking(fn(File $file) => $folder->appendNode($file))
                ->isFile()->make(['name' => $name]);
        });

        // How many files contains in root node in database
        $this->assertCount(4, $root->children);

        // set config for page size in files list.
        Config::set('app.my_files.per_page', 2);

        $this->get('/file')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->where('parentId', $root->id)
                ->has('files.data', 2)
                ->where('files.meta.total', 4)
                ->whereContains('files.links.next', static function (string $value) {
                    return Str::contains($value, '/file?page=2');
                })
            );
    }

    public function test_not_auth_user_redirect_to_login(): void
    {
        $this->followingRedirects()->get('/file')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('Auth/Login')
                ->where('auth.user', null)
            );
    }

    public function test_user_list_for_auth_user(): void
    {
        // Make tree for other user
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);
        $otherRoot = File::makeRootByUser($otherUser);

        collect(['f1.png', 'f2.png'])->each(function (string $name) use ($otherRoot) {
            File::factory()
                ->afterMaking(fn(File $file) => $otherRoot->appendNode($file))
                ->isFile()->make(['name' => $name]);
        });

        // Make request user
        $user = User::factory()->create();
        $this->actingAs($user);
        $root = File::makeRootByUser($user);

        $this->actingAs($user)->get('/file')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('MyFiles')
                ->where('parentId', $root->id)
                ->has('files.data', 0)
            );
    }

    public function test_user_not_verified(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->followingRedirects()
            ->get('/file')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('Auth/VerifyEmail')
                ->has('auth.user.name')
                ->where('auth.user.email', $user->email)
                ->where('auth.user.email_verified_at', null)
            );
    }

    public function test_user_with_empty_files_with_check_props(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $root = File::makeRootByUser($user);

        $this->get('/file')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('MyFiles')
                ->where('parentId', $root->id)
                ->has('ancestors.data.0', function (AssertableInertia $item) use ($root) {
                    $item->where('parentId', null)
                        ->where('id', $root->id)
                        ->where('isFolder', true)
                        ->etc();
                })
                ->where('auth.user.id', $user->id)
                // list of files is empty
                ->has('files.data', 0)
                // has pagination info
                ->has('files.links.next')
                ->has('files.meta.total')
            );
    }

    public function test_user_with_route_with_parent_folder_id(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $root = File::makeRootByUser($user);

        $this->get('/file/' . $root->id)
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('MyFiles')
                ->where('parentId', $root->id)
            );
    }

    public function test_user_without_root_folder(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/file')
            ->assertNotFound();
    }
}
