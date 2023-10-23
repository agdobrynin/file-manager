<?php

namespace Tests\Feature\Models;

use App\Dto\MyFilesListFilterDto;
use App\Models\File;
use App\Models\User;
use App\VO\FileFolderVO;
use App\VO\FileVO;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class FileScopeFilesListTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_files_list_for_user_1(): void
    {
        $params = $this->makeTestDataset();

        // For user root list
        $resQuery = File::filesList($params->user1, new MyFilesListFilterDto(), $params->rootUser1);

        $this->assertInstanceOf(Builder::class, $resQuery);
        $this->assertCount(3, $resQuery->get());
    }

    /**
     * @return __anonymous@4321
     */
    protected function makeTestDataset(): object
    {
        $randFileParams = static fn(string $suffix = null) => (new FileVO(
            name: fake()->name . ($suffix ?: ''),
            mime: fake()->mimeType(),
            size: 100,
        ))->toArray();

        $randFolderParams = static fn(string $prefix = null) => (new FileFolderVO(
            ($prefix ?: '') . fake()->name)
        )->toArray();

        // User #1 branch
        $user1 = User::factory()->create();
        Auth::setUser($user1);
        $root1 = File::makeRootByUser($user1);
        File::create($randFileParams('.jpg'), $root1);
        File::create($randFileParams('.jpg'), $root1);
        File::create($randFolderParams('Folder '), $root1);

        // User #2 branch
        $user2 = User::factory()->create();
        Auth::setUser($user2);
        $root2 = File::makeRootByUser($user2);
        $subFolder = File::create([
            ...$randFolderParams('Folder '),
            'children' => [
                [... $randFileParams('.jpg')],
                [... $randFileParams('.jpg')],
                [... $randFileParams('.jpg')],
                [... $randFileParams('.png')],
                [... $randFolderParams()],
            ],
        ], $root2);
        /** @var File $fileTrash file move to trash */
        $fileTrash = File::create($randFileParams('.jpg'), $root2);
        File::create($randFileParams('.jpg'), $root2);
        /** @var File $file1 has favorite mark */
        $file1 = File::create($randFileParams(), $root2);

        // File in root1 branch is favorite mark
        $file1->favorite()->create([
            'user_id' => $user2->id,
            'file_id' => $file1->id,
        ]);
        // Delete one item
        $fileTrash->delete();

        return new class($user1, $user2, $subFolder, $root1, $root2) {
            public function __construct(
                public User $user1,
                public User $user2,
                public File $subFolderUser2,
                public File $rootUser1,
                public File $rootUser2,

            )
            {
            }
        };
    }

    public function test_all_dataset(): void
    {
        $this->makeTestDataset();

        // All files in DB
        $this->assertCount(13, File::all());
        $this->assertCount(14, File::withTrashed()->get());

    }

    public function test_sub_folder_for_user_2(): void
    {
        $params = $this->makeTestDataset();
        // For user by sub folder
        $dto = new MyFilesListFilterDto();
        $resSubFolderQuery = File::filesList($params->user2, $dto, $params->subFolderUser2);
        $resSubFolder = $resSubFolderQuery->get();

        $this->assertCount(5, $resSubFolder);
        $this->assertInstanceOf(Collection::class, $resSubFolder);
        $this->assertInstanceOf(File::class, $resSubFolder->first());
    }

    public function test_sub_folder_for_user_1(): void
    {
        $params = $this->makeTestDataset();
        // For user by sub folder
        $dto = new MyFilesListFilterDto();
        $resSubFolderQuery = File::filesList($params->user1, $dto, $params->subFolderUser2);
        $resSubFolder = $resSubFolderQuery->get();

        $this->assertCount(0, $resSubFolder);
        $this->assertInstanceOf(Collection::class, $resSubFolder);
    }

    public function test_filter_favorite_for_user1(): void
    {
        $params = $this->makeTestDataset();
        $dto = new MyFilesListFilterDto(onlyFavorites: true);
        $resSubFolderWithFilterQuery = File::filesList($params->user1, $dto, $params->rootUser1);
        $resSubFolderWithFilter = $resSubFolderWithFilterQuery->get();

        $this->assertCount(0, $resSubFolderWithFilter);
        $this->assertInstanceOf(Collection::class, $resSubFolderWithFilter);

    }

    public function test_filter_favorite_for_user2(): void
    {
        $params = $this->makeTestDataset();
        $dto = new MyFilesListFilterDto(onlyFavorites: true);
        $resSubFolderWithFilterQuery = File::filesList($params->user2, $dto, $params->rootUser2);
        $resSubFolderWithFilter = $resSubFolderWithFilterQuery->get();

        $this->assertCount(1, $resSubFolderWithFilter);
        $this->assertInstanceOf(File::class, $resSubFolderWithFilter->first());
    }

    public function test_search_files_for_user_2(): void
    {
        $params = $this->makeTestDataset();
        // with search string
        $dto = new MyFilesListFilterDto(search: '.jpg');
        $resFilterQuery = File::filesList($params->user2, $dto, $params->rootUser2);
        $this->assertCount(4, $resFilterQuery->get());
    }

    public function test_search_files_for_user_1(): void
    {
        $params = $this->makeTestDataset();
        // with search string
        $dto = new MyFilesListFilterDto(search: '.jpg');
        $resFilterQuery = File::filesList($params->user1, $dto, $params->rootUser1);
        $this->assertCount(2, $resFilterQuery->get());
        $this->assertInstanceOf(File::class, $resFilterQuery->first());
        $this->assertStringEndsWith('.jpg', $resFilterQuery->first()->name);
    }

    public function test_search_files_for_user_2_folder(): void
    {
        $params = $this->makeTestDataset();
        // with search string
        $dto = new MyFilesListFilterDto(search: 'Folder');
        $resFilterQuery = File::filesList($params->user2, $dto, $params->rootUser2);
        $this->assertCount(1, $resFilterQuery->get());
        $this->assertStringStartsWith('Folder', $resFilterQuery->first()->name);
    }
}
