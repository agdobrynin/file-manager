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

    public static function dataTestSearchAndFavoriteInMyFiles(): \Generator
    {
        yield 'search ".png" and favorite only' => [
            'url' => '/file?search=.png&onlyFavorites=1',
            'perPage' => 2,
            'expectFiles' => [
                ['path' => '/Folder1/f-2-2.png', 'isFavorite' => true],
                ['path' => '/Folder1/f-2-1.png', 'isFavorite' => true],
            ],
            'totalFiles' => 3,
        ];

        yield 'favorite only in root folder' => [
            'url' => '/file?onlyFavorites=1',
            'perPage' => 2,
            'expectFiles' => [
                ['path' => '/Folder1', 'isFavorite' => true],
                ['path' => '/f-1-4.doc', 'isFavorite' => true],
            ],
            'totalFiles' => 3,
        ];

        yield 'favorite only in root folder page 2' => [
            'url' => '/file?onlyFavorites=1&page=2',
            'perPage' => 2,
            'expectFiles' => [
                ['path' => '/f-1-1.png', 'isFavorite' => true],
            ],
            'totalFiles' => 3,
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
                ->url('/login')
                ->where('auth.user', null)
            );
    }

    /**
     * @dataProvider dataTestSearchAndFavoriteInMyFiles
     */
    public function test_search_and_favorite_in_my_files(string $url, int $perPage, array $expectFiles, int $totalFiles): void
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
            $paths = [
                '/Folder1',
                '/f-1-1.png',
                '/f-1-4.doc',
                '/Folder1/f-2-1.png',
                '/Folder1/f-2-2.png',
                '/Folder1/f-2-3.xls',
            ];
            if (in_array($file->path, $paths, true)) {
                FileFavorite::factory()->for($file, 'file')
                    ->for($file->user, 'user')
                    ->create();
            }
        });

        Config::set('app.my_files.per_page', $perPage);

        $this->actingAs($user)->get($url)
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('MyFiles')
                ->where('files.meta.total', $totalFiles)
                ->whereContains('files', function (array $data) use ($expectFiles) {
                    $intersect = array_uintersect_assoc(
                        $expectFiles,
                        $data,
                        static fn($expectItem, $dataItem) => $expectItem !== array_intersect_assoc($expectItem, $dataItem)
                    );
                    $this->assertEqualsCanonicalizing($expectFiles, $intersect);

                    return true;
                })
            );
    }

    public function test_unauthorized_action_for_parent_folder_not_owner(): void
    {
        $otherUser = User::factory()->create();
        $otherRoot = File::factory()->isFolder($otherUser)->createQuietly();

        $user = User::factory()->create();
        $root = File::factory()->isFolder($user)->createQuietly();

        $this->actingAs($user)->get('/file/' . $otherRoot->id)
            ->assertForbidden();
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
                ->url('/verify-email')
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
