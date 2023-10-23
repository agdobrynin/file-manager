<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\DiskEnum;
use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FileControllerMethodDownloadTest extends TestCase
{
    use RefreshDatabase;

    public static function dataMethodAvailable(): \Generator
    {
        yield 'method post' => ['method' => 'post'];
        yield 'method delete' => ['method' => 'delete'];
        yield 'method patch' => ['method' => 'patch'];
    }

    /**
     * @dataProvider dataMethodAvailable
     */
    public function test_method_available(string $method): void
    {
        $this->actingAs(User::factory()->create())
            ->{$method}('/file/download')
            ->assertMethodNotAllowed();
    }

    public function test_download_can_not_read_source_files(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $root = File::makeRootByUser($user);
        File::factory(2)
            ->afterMaking(fn(File $file) => $root->appendNode($file))
            ->make();

        $this->actingAs($user)->get('/file/download/' . $root->id . '?all=1')
            ->assertStatus(400)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_download_empty_folder(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $root = File::makeRootByUser($user);
        $subFolder = File::factory()->isFolder()->create(['name' => 'Sub Folder']);
        $root->appendNode($subFolder);

        $this->actingAs($user)->get('/file/download/' . $root->id . '?all=&ids[]=' . $subFolder->id)
            ->assertStatus(400)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_download_folder(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $root = File::makeRootByUser($user);
        $subFolder = File::factory()->isFolder()->create(['name' => 'Sub Folder']);
        $root->appendNode($subFolder);
        $files = File::factory(2)
            ->afterMaking(fn(File $file) => $subFolder->appendNode($file))
            ->make(['disk' => DiskEnum::LOCAL]);
        // make storage
        $storage = Storage::fake(DiskEnum::LOCAL->value);
        /** @var File $file */
        foreach ($files as $file) {
            $storage->put($file->storage_path, 'small content here');
        }

        $this->actingAs($user)->get('/file/download/' . $root->id . '?all=&ids[]=' . $subFolder->id)
            ->assertOk()
            ->assertHeader(
                'content-disposition',
                'attachment; filename="Sub Folder.zip"'
            )
            ->assertHeader('content-type', 'application/zip');
    }

    public function test_download_all_files_in_folder(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $root = File::makeRootByUser($user);
        $subFolder = File::factory()->isFolder()->create(['name' => 'Sub Folder']);
        $root->appendNode($subFolder);
        $files = File::factory(2)
            ->afterMaking(fn(File $file) => $subFolder->appendNode($file))
            ->make(['disk' => DiskEnum::LOCAL]);
        // make storage
        $storage = Storage::fake(DiskEnum::LOCAL->value);
        /** @var File $file */
        foreach ($files as $file) {
            $storage->put($file->storage_path, 'small content here');
        }

        $this->actingAs($user)->get('/file/download/' . $subFolder->id . '?all=1')
            ->assertOk()
            ->assertHeader(
                'content-disposition',
                'attachment; filename="Sub Folder.zip"'
            )
            ->assertHeader('content-type', 'application/zip');
    }

    public function test_download_one_file(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $root = File::makeRootByUser($user);
        $files = File::factory(2)
            ->afterMaking(fn(File $file) => $root->appendNode($file))
            ->make([
                'disk' => DiskEnum::LOCAL,
            ]);
        // make storage
        $storage = Storage::fake(DiskEnum::LOCAL->value);
        $storage->put($files[1]->storage_path, fake()->image);

        $this->actingAs($user)->get('/file/download/' . $root->id . '?all=false&ids[]=' . $files[1]->id)
            ->assertOk()
            ->assertHeader(
                'content-disposition',
                'attachment; filename=' . $files[1]->name
            );
    }

    public function test_download_success_all_from_root(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $root = File::makeRootByUser($user);
        $files = File::factory(2)
            ->afterMaking(fn(File $file) => $root->appendNode($file))
            ->make(['disk' => DiskEnum::LOCAL]);
        // make storage
        $storage = Storage::fake(DiskEnum::LOCAL->value);
        /** @var File $file */
        foreach ($files as $file) {
            $storage->put($file->storage_path, 'small content here');
        }

        $this->actingAs($user)->get('/file/download/' . $root->id . '?all=1')
            ->assertOk()
            ->assertHeader(
                'content-disposition',
                'attachment; filename="My files.zip"'
            )
            ->assertHeader('content-type', 'application/zip');
    }

    public function test_download_with_parent_folder_not_owner(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $rootOther = File::makeRootByUser($otherUser);
        File::factory(2)
            ->afterMaking(fn(File $file) => $rootOther->appendNode($file))
            ->make();

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->actingAs($user)->get('/file/download/' . $rootOther->id . '?all=1')
            ->assertForbidden();
    }

    public function test_patent_folder_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/file/download/10000')
            ->assertNotFound();
    }

    public function test_user_not_auth(): void
    {
        $this->followingRedirects()
            ->get('/file/download')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('Auth/Login')
                ->url('/login')
                ->where('auth.user', null)
            );
    }

    public function test_user_not_verified(): void
    {
        $user = User::factory()->unverified()->create();

        $this->followingRedirects()
            ->actingAs($user)
            ->get('/file/download')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('Auth/VerifyEmail')
                ->url('/verify-email')
                ->where('auth.user.id', $user->id)
            );
    }
}
