<?php

namespace Tests\Feature\Services;

use App\Dto\DestroyFileFromStorageDto;
use App\Models\File;
use App\Models\User;
use App\Services\FilesDestroyService;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class FilesDestroyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_files_destroy_collection_not_contain_file_models(): void
    {
        $collection = User::factory(10)->create();
        $collection->push('abc', 'aaa', 'ccc');
        $service = new FilesDestroyService();

        $this->assertCount(0, $service->destroy($collection));
    }

    public function test_files_destroy_success(): void
    {
        $user = User::factory()->create();
        Auth::shouldReceive('id')->andReturn($user->id);

        /** @var File $root */
        $root = File::factory()->isFolder()->create(['name' => $user->email]);
        $filesInRoot = File::factory(3)
            ->state(new Sequence(fn () => ['storage_path' => '/files/'.$root->id.'/'.Str::uuid()]))
            ->afterCreating(fn (File $file) => $file->appendToNode($root)->save())
            ->create();
        $subFolder = File::factory()->isFolder()
            ->afterCreating(fn (File $file) => $file->appendToNode($root)->save())
            ->create();
        $filesInSubFolder = File::factory(2)
            ->state(new Sequence(fn () => ['storage_path' => '/files/'.$subFolder->id.'/'.Str::uuid()]))
            ->afterCreating(fn (File $file) => $file->appendToNode($subFolder)->save())
            ->create();

        $this->assertDatabaseHas(File::class, $subFolder->toArray());
        $this->assertDatabaseHas(File::class, $filesInRoot[0]->toArray());
        $this->assertDatabaseHas(File::class, $filesInSubFolder[0]->toArray());

        $service = new FilesDestroyService();
        $res = $service->destroy($root->children);

        $this->assertCount(5, $res);
        $this->assertContainsOnlyInstancesOf(DestroyFileFromStorageDto::class, $res);

        $this->assertDatabaseMissing(File::class, $filesInRoot[0]->toArray());
        $this->assertDatabaseMissing(File::class, $filesInSubFolder[0]->toArray());
        $this->assertDatabaseMissing(File::class, $subFolder->toArray());

        $root->refresh();
        $this->assertCount(0, $root->children);
    }
}
