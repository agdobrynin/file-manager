<?php

namespace Tests\Feature\Models;

use App\Dto\FilesListFilterDto;
use App\Models\File;
use App\Models\User;
use App\VO\FileFolderVO;
use App\VO\FileVO;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class FileScopeFilesInTrashTest extends TestCase
{
    use RefreshDatabase;

    public function test_files_in_trash_empty(): void
    {
        $params = $this->makeDataset();
        $query = File::filesInTrash($params->user);

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertCount(0, $query->get());
    }

    public function test_files_in_trash(): void
    {
        $params = $this->makeDataset();
        //delete 2 children items.
        $params->root->children()->each(fn (File $file) => $file->deleteQuietly());

        $query = File::filesInTrash($params->user);

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertCount(2, $query->get());
    }

    public function test_files_in_trash_with_search(): void
    {
        $params = $this->makeDataset();
        $params->root->descendants()->each(function (File $file) {
            if (str_starts_with($file->name, 'image')) {
                $file->deleteQuietly();
            }
        });

        $dto = new FilesListFilterDto(search: 'image');
        $query = File::filesInTrash($params->user, $dto);

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertCount(3, $query->get());
    }

    protected function makeDataset(): object
    {
        $randFolder = static fn (string $name = '') => (new FileFolderVO($name ?: fake()->name))->toArray();
        $randFile = static fn (string $name = '') => (new FileVO(name: $name ?: fake()->name, mime: fake()->mimeType(), size: fake()->numberBetween(10, 100)))->toArray();

        $user1 = User::factory()->create();
        Auth::setUser($user1);
        $rootForUser1 = File::makeRootByUser($user1);
        File::create($randFile(), $rootForUser1);
        $folder1 = File::create($randFolder(), $rootForUser1);
        File::create($randFile(), $folder1);
        File::create($randFile(), $folder1);

        // Work tree.
        $user2 = User::factory()->create();
        Auth::setUser($user2);
        $rootForUser2 = File::makeRootByUser($user2);
        File::create([
            ...$randFolder(),
            'children' => [
                $randFile('image-1.png'),
                $randFile('image-2.png'),
                $randFolder('image-3.png'),
            ],
        ], $rootForUser2);
        File::create($randFile(), $rootForUser2);

        return new class($rootForUser2, $user2)
        {
            public function __construct(
                public File $root,
                public User $user,
            ) {
            }
        };
    }
}
