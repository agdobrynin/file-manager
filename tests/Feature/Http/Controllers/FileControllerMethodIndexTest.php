<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\File;
use App\Models\FileFavorite;
use App\Models\User;
use App\VO\FileFolderVO;
use Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FileControllerMethodIndexTest extends TestCase
{
    use RefreshDatabase;

    public static function dataForTestFilesListWithPagination(): Generator
    {
        yield 'for page 1 or default' => [
            'url' => '/file',
            'howManyFiles' => 3,
            'nextPage' => '/file?page=2',
        ];

        yield 'for page 2' => [
            'url' => '/file?page=2',
            'howManyFiles' => 2,
            'nextPage' => null,
        ];
    }

    public function test_file_resource_property(): void
    {
        $user = User::factory()->create();
        // make files
        $this->makeFilesTreeForUser(['f-1-1.png'], $user);

        $this->actingAs($user)->get('/file')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                // test fileResource structure with check types
                ->whereType('files.data.0.id', 'integer')
                ->whereType('files.data.0.isFavorite', 'boolean')
                ->whereType('files.data.0.name', 'string')
                ->whereType('files.data.0.disk', 'string')
                ->whereType('files.data.0.path', 'string')
                ->whereType('files.data.0.parentId', 'integer')
                ->whereType('files.data.0.isFolder', 'boolean')
                ->whereType('files.data.0.mime', ['string', 'null'])
                ->whereType('files.data.0.size', ['integer', 'null'])
                ->whereType('files.data.0.owner', 'string')
                ->whereType('files.data.0.createdAt', 'string')
                ->whereType('files.data.0.updatedAt', 'string')
                ->whereType('files.data.0.createdBy', 'integer')
                ->whereType('files.data.0.updatedBy', 'integer')
                ->whereType('files.data.0.deletedAt', ['string', 'null'])
                // check ancestors props
                ->whereType('ancestors.data.0.id', 'integer')
                ->whereType('ancestors.data.0.isFolder', 'boolean')
                ->whereType('ancestors.data.0.name', 'string')
                ->whereType('ancestors.data.0.parentId', ['integer', 'null'])
            );
    }

    protected function makeFilesTreeForUser(array $names, User $user): File
    {
        $this->actingAs($user);
        $root = File::makeRootByUser($user);

        $makeFilesTree = static function (array $names, File $parent) use (&$makeFilesTree) {
            foreach ($names as $key => $name) {
                if (is_array($name)) {
                    $folder = File::create((new FileFolderVO(name: $key))->toArray(), $parent);
                    $makeFilesTree($name, $folder);
                } else {
                    File::factory()->afterMaking(fn(File $file) => $parent->appendNode($file))
                        ->isFile()->make(['name' => $name]);
                }
            }
        };

        $makeFilesTree($names, $root);

        return $root;
    }

    /** @dataProvider dataForTestFilesListWithPagination */
    public function test_files_list_with_pagination(string $url, int $howManyFiles, ?string $nextPage): void
    {
        $user = User::factory()->create();
        // make files
        $this->makeFilesTreeForUser([
            'f-1-1.png', 'f-1-2.png', 'f-1-3.jpg', 'f-1-4.doc',
            'Folder1' => ['f-2-1.png', 'f-2-2.png']
        ], $user);

        // set config for page size in files list.
        Config::set('app.my_files.per_page', 3);

        $this->actingAs($user)->get($url)
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->has('files.data', $howManyFiles)
                // Folder is first in list
                ->where('files.meta.total', 5)
                ->where('files.links.next', $nextPage ? Config::get('app.url') . $nextPage : null)
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

    public function test_search_and_favorite_in_my_files(): void
    {
        $user = User::factory()->create();
        // make files
        $root = $this->makeFilesTreeForUser([
            'f-1-1.png', 'f-1-2.png', 'f-1-3.png', 'f-1-4.doc',
            'Folder1' => ['f-2-1.png', 'f-2-2.png', 'f-2-3.xls'],
        ], $user);

        // Favorite and search display all descendants
        // Make is favorite files
        $root->refresh()->descendants()->get()->each(function (File $file) {
            if (in_array($file->name, ['Folder1', 'f-1-1.png', 'f-2-1.png', 'f-2-3.xls'])) {
                FileFavorite::factory()->for($file, 'file')
                    ->for($file->user, 'user')
                    ->create();
            }
        });

        $this->actingAs($user)->get('/file?search=.png&onlyFavorites=1')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('MyFiles')
                ->where('files.meta.total', 2)
                ->has('files.data', 2)
                ->where('files.data.0.name', 'f-2-1.png')
                ->where('files.data.0.isFavorite', true)
                ->where('files.data.1.name', 'f-1-1.png')
                ->where('files.data.1.isFavorite', true)
            );
        // Favorites in root folder
        $this->actingAs($user)->get('/file?onlyFavorites=1')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('MyFiles')
                ->where('files.meta.total', 2)
                ->has('files.data', 2)
                ->where('files.data.0.name', 'Folder1')
                ->where('files.data.0.isFavorite', true)
                ->where('files.data.1.name', 'f-1-1.png')
                ->where('files.data.1.isFavorite', true)
            );
        // Favorites in sub folder
        $subFolder = $root->children->firstWhere(fn(File $file) => $file->isFolder());
        $this->actingAs($user)->get('/file/' . $subFolder->id . '?onlyFavorites=1')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('MyFiles')
                ->where('files.meta.total', 2)
                ->has('files.data', 2)
                ->where('files.data.0.name', 'f-2-3.xls')
                ->where('files.data.0.isFavorite', true)
                ->where('files.data.1.name', 'f-2-1.png')
                ->where('files.data.1.isFavorite', true)
            );
    }

    public function test_search_in_my_files_with_pagination(): void
    {
        $user = User::factory()->create();
        // make files
        $this->makeFilesTreeForUser([
            'f-1-1.png', 'f-1-4.doc',
            'Folder1' => [
                'f-2-1.png', 'f-2-2.xls',
                'Sub Folder2' => [
                    'f-2-2-1.png', 'f-2-2-2.doc'
                ]
            ]
        ], $user);

        // set config for page size in files list.
        Config::set('app.my_files.per_page', 2);

        $this->actingAs($user)->get('/file?search=.png')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('MyFiles')
                ->where('files.meta.total', 3)
                ->has('files.data', 2) // per_page = 2, but all find files 3
                ->where('files.data.0.name', 'f-2-2-1.png')
                ->where('files.data.1.name', 'f-2-1.png')
            );
        //page 2
        $this->actingAs($user)->get('/file?search=.png&page=2')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('MyFiles')
                ->where('files.meta.total', 3)
                ->has('files.data', 1)
                ->where('files.data.0.name', 'f-1-1.png')
            );
    }

    public function test_user_list_for_auth_user(): void
    {
        // Make tree for other user
        $otherUser = User::factory()->create();
        $this->makeFilesTreeForUser(['f1.png', 'f2.png'], $otherUser);

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
