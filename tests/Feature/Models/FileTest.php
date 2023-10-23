<?php

namespace Tests\Feature\Models;

use App\Enums\DiskEnum;
use App\Models\File;
use App\Models\FileFavorite;
use App\Models\FileShare;
use App\Models\User;
use App\VO\FileFolderVO;
use App\VO\FileVO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    public static function rootData(): \Generator
    {
        yield 'root exist' => [
            'data' => function () {
                $user = User::factory()->create();
                Auth::shouldReceive('id')->andReturn($user->id);
                $file = File::makeRootByUser($user);

                return ['user' => $user, 'file' => $file];
            }
        ];

        yield 'root not exist' => [
            'data' => function () {
                $user = User::factory()->create();
                Auth::shouldReceive('id')->andReturn($user->id);

                return ['user' => $user, 'file' => null];
            }
        ];
    }

    /**
     * @dataProvider  rootData
     */
    public function test_MakeRoot(\Closure $data): void
    {
        ['file' => $file, 'user' => $user] = $data();

        $createdRoot = File::makeRootByUser($user);

        if ($file) {
            $this->assertEquals($file->id, $createdRoot->id);
        }

        $this->assertDatabaseCount(File::class, 1);
        $this->assertModelExists($createdRoot);
    }

    public static function dataFolderByRoot(): \Generator
    {
        yield 'root not detected and user is null' => [
            'data' => fn() => ['user' => null, 'file' => null],
            'exception' => ModelNotFoundException::class,
        ];

        yield 'root not detected and user not owner root' => [
            'data' => function () {
                $user = User::factory()->create();
                $user2 = User::factory()->create();
                Auth::shouldReceive('id')->andReturn($user2->id);
                $fileRoot = File::makeRootByUser($user2);

                return ['user' => $user, 'file' => $fileRoot];
            },
            'exception' => ModelNotFoundException::class,
        ];

        yield 'root not detected and user has some files' => [
            'data' => function () {
                $user = User::factory()->create();
                Auth::shouldReceive('id')->andReturn($user->id);
                $file = File::factory()->isFile()->create();

                return ['user' => $user, 'file' => $file];
            },
            'exception' => ModelNotFoundException::class,
        ];

        yield 'root detected' => [
            'data' => function () {
                $user = User::factory()->create();
                Auth::shouldReceive('id')->andReturn($user->id);
                $file = File::makeRootByUser($user);

                return ['user' => $user, 'file' => $file];
            },
            'exception' => null,
        ];
    }

    /**
     * @dataProvider dataFolderByRoot
     */
    public function test_folder_by_root(\Closure $data, ?string $exception): void
    {
        if ($exception) {
            $this->expectException($exception);
        }

        ['file' => $file, 'user' => $user] = $data();

        $foundRoot = File::rootFolderByUser($user);

        if (!$exception) {
            $this->assertEquals($foundRoot->id, $file->id);
        }
    }

    public function test_create_path_for_file(): void
    {
        $user = User::factory()->create();
        Auth::shouldReceive('id')
            ->andReturn($user->id);

        $root = File::makeRootByUser($user);
        $folder1 = File::create([...(new FileFolderVO('Foo'))->toArray()], $root);
        $subFolder1 = File::create([...(new FileFolderVO('Bar'))->toArray()], $folder1);
        $file = File::create([
            ...(new FileVO(
            name: 'baz.png',
            mime: 'img/img',
            size: 100,
            path: null,
            storagePath: null
            ))->toArray(),
        ], $subFolder1);

        $this->assertEquals('/Foo', $folder1->path);
        $this->assertTrue($folder1->isOwnedByUser($user));

        $this->assertEquals('/Foo/Bar', $subFolder1->path);
        $this->assertTrue($subFolder1->isOwnedByUser($user));

        $this->assertEquals('/Foo/Bar/baz.png', $file->path);
        $this->assertTrue($file->isOwnedByUser($user));

        // relation
        $this->assertInstanceOf(User::class, $folder1->user);
        $this->assertInstanceOf(User::class, $subFolder1->user);
        $this->assertInstanceOf(User::class, $file->user);
    }

    public function test_favorite(): void
    {
        $user = User::factory()->create();
        Auth::shouldReceive('id')->andReturn($user->id);

        $file = File::factory()
            ->for($user)
            ->hasFavorite(['user_id' => $user->id])
            ->isFile()
            ->create();

        // relation
        $this->assertInstanceOf(FileFavorite::class, $file->favorite);
    }

    public function test_file_share(): void
    {
        $user = User::factory()->create();
        $forUser = User::factory()->create();
        Auth::shouldReceive('id')->andReturn($user->id);

        $file = File::factory()
            ->for($user)
            ->has(FileShare::factory()->for($forUser, 'forUser'), 'fileShare')
            ->isFile()
            ->create();

        // relation
        $this->assertInstanceOf(FileShare::class, $file->fileShare->first());
        $this->assertInstanceOf(Collection::class, $file->fileShare);
    }

    public function test_attribute_owner_me(): void
    {
        $user = User::factory()->create();
        Auth::shouldReceive('id')->andReturn($user->id);
        $file = File::factory()->for($user)->isFile()->create();
        $this->assertEquals('me', $file->owner);
    }

    public function test_attribute_owner_other(): void
    {
        $user = User::factory()->create(['name' => 'Superman']);
        Auth::setUser($user);
        /** @var File $file */
        $file = File::factory()->for($user)->isFile()->create();

        $userOther = User::factory()->create();
        Auth::setUser($userOther);

        $this->assertEquals('Superman', $file->owner);
    }

    public function test_attribute_disk(): void
    {
        $user = User::factory()->create(['name' => 'Superman']);
        Auth::shouldReceive('id')->andReturn($user->id);
        $file = File::factory()->for($user)->isFile()->create(['disk' => DiskEnum::CLOUD]);

        $this->assertEquals(DiskEnum::CLOUD, $file->disk);
    }
}
