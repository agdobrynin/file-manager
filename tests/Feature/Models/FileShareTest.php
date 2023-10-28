<?php

namespace Tests\Feature\Models;

use App\Dto\FilesListFilterDto;
use App\Models\File;
use App\Models\FileShare;
use App\Models\User;
use App\VO\FileFolderVO;
use App\VO\FileVO;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_share_by_file_owner(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // files with file shares for user 2
        File::factory(3)->isFile($user2)
            ->has(FileShare::factory()->for($user2, 'forUser'))
            ->createQuietly();

        $filesForUser1 = File::factory(5)
            ->isFolder($user1)
            ->sequence(
                fn (Sequence $sequence) => (new FileFolderVO(name: 'Folder #'.$sequence->index))->toArray()
            )->createQuietly();
        // Make 2 share for user 1
        FileShare::factory()->for($user1, 'forUser')->for($filesForUser1[0])
            ->createQuietly();
        FileShare::factory()->for($user1, 'forUser')->for($filesForUser1[1])
            ->createQuietly();

        $query = FileShare::fileShareByFileOwner($user1);

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertCount(2, $query->get());
        $this->assertEquals('Folder #0', $query->get()[0]->file->name);
        $this->assertEquals('Folder #1', $query->get()[1]->file->name);
        $this->assertEquals($user1->name, $query->get()[0]->file->user->name);
        $this->assertEquals($user1->name, $query->get()[1]->file->user->name);
    }

    public function test_file_share_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Data for other user
        File::factory(20)
            ->isFile($user1)
            ->state(
                (new FileVO(name: fake()->title.'.png', mime: 'image/image', size: 1))->toArray(),
            )
            ->has(fileShare::factory()->for($user2, 'forUser'))
            ->createQuietly();

        // Work data
        File::factory(10)
            ->for($user2)
            ->for($user2, 'userUpdate')
            ->state(new Sequence(
                (new FileFolderVO(name: 'Folder '.fake()->randomDigit()))->toArray(),
                (new FileVO(name: fake()->title.'.png', mime: 'image/image', size: 1))->toArray(),
            ))
            ->has(fileShare::factory()->for($user1, 'forUser'))
            ->createQuietly();

        $dto = new FilesListFilterDto(search: '.png');
        $query = FileShare::fileShareByUser($user2, $dto);

        $this->assertInstanceOf(Builder::class, $query);

        $shredFiles = $query->get();

        $this->assertCount(5, $shredFiles);
        $this->assertInstanceOf(File::class, $shredFiles[0]->file);
        $this->assertEquals($user1->id, $shredFiles[0]->forUser->id);
    }

    public function test_file_share_for_user_by_file(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $files = File::factory(5)
            ->isFile($user1)
            ->state(new Sequence(
                fn (Sequence $sequence) => ['name' => 'File #'.$sequence->index]
            ))->createQuietly();
        // Make 2 share for user 1
        FileShare::factory()->for($user2, 'forUser')->for($files[0])
            ->createQuietly();
        FileShare::factory()->for($user2, 'forUser')->for($files[1])
            ->createQuietly();

        $query = FileShare::fileShareForUserByFile($user2, $files);

        $this->assertInstanceOf(Builder::class, $query);

        $result = $query->get();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals('File #0', $result[0]->file->name);
        $this->assertEquals('File #1', $result[1]->file->name);
    }

    public function test_file_share_for_user_or_by_user(): void
    {
        $fileShareByUser = User::factory()->create();
        $fileShareForUser = User::factory()->create();

        // Files without share
        File::factory(8)
            ->for($fileShareByUser)
            ->for($fileShareByUser, 'userUpdate')
            ->createQuietly();
        // Files with share for $fileShareForUser
        File::factory(2)
            ->for($fileShareByUser)
            ->for($fileShareByUser, 'userUpdate')
            ->has(fileShare::factory()->for($fileShareForUser, 'forUser'))
            ->createQuietly();

        $query = FileShare::fileShareForUserOrByUser($fileShareByUser);

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertCount(2, $query->get());

        $query2 = FileShare::fileShareForUserOrByUser($fileShareForUser);

        $this->assertInstanceOf(Builder::class, $query2);
        $this->assertCount(2, $query2->get());
    }

    public function test_file_share_for_user_with_filter(): void
    {
        $fileShareForUser = User::factory()->create();

        User::factory()->has(
            File::factory(5, state: ['updated_by' => 1])
                ->state(new Sequence(
                    ['name' => 'file.png'], // <-- filter it file
                    ['name' => 'folders-with-png'],
                    ['name' => 'document.docx'],
                    ['name' => 'file-qw.png'], // <-- filter it file
                    ['name' => 'new-site.jpg'],
                ))
                ->has(
                    FileShare::factory()
                        ->for($fileShareForUser, 'forUser'),
                    'fileShare'
                ),
            'files'
        )->createQuietly();

        $dto = new FilesListFilterDto(search: '.png');
        $query = FileShare::fileShareForUserWithFilter($fileShareForUser, $dto);

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertCount(2, $query->get());
    }

    public function test_relation(): void
    {
        $user = User::factory()->create();
        $file = File::factory()->isFile($user)
            ->createQuietly(['name' => 'my-file.doc']);

        $fileShare = FileShare::factory()
            ->for($user, 'forUser')
            ->for($file)->createQuietly();

        $this->assertInstanceOf(User::class, $fileShare->forUser);
        $this->assertEquals($user->id, $fileShare->forUser->id);
        $this->assertInstanceOf(File::class, $fileShare->file);
        $this->assertEquals('my-file.doc', $fileShare->file->name);
    }

    public function test_share_for_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Work data
        File::factory(10)
            ->for($user2)
            ->for($user2, 'userUpdate')
            ->createQuietly()
            ->slice(0, 2)
            ->each(function (File $file) use ($user1) {
                FileShare::factory()
                    ->for($user1, 'forUser')
                    ->for($file)
                    ->createQuietly();
            });

        $query = FileShare::fileShareForUser($user1);

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertCount(2, $query->get());

        $query = FileShare::fileShareForUser($user2);

        $this->assertCount(0, $query->get());
    }
}
