<?php

namespace Tests\Feature\Http\Requests;

use App\Enums\DiskEnum;
use App\Http\Requests\FavoriteRequest;
use App\Models\File;
use App\Models\User;
use App\VO\FileVO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FavoriteRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_success(): void
    {
        ['user' => $userOwner, 'file' => $file] = $this->makeTree();

        $request = FavoriteRequest::create('/file/favorite', 'PATCH');
        $request->setUserResolver(fn() => $userOwner);

        $validator = Validator::make(['id' => $file->id], $request->rules());

        $this->assertTrue($validator->passes());
        $this->assertEquals([], $validator->errors()->keys());
        $this->assertTrue($request->authorize());
    }

    protected function makeTree(): array
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $root = File::makeRootByUser($user);
        $fileVo = new FileVO(
            name: 'my file.jpg',
            mime: 'image/jpeg',
            size: '1000',
            disk: DiskEnum::LOCAL,
            path: '/file.jpg',
            storagePath: 'files/2/my_file_in_store.jpg',
        );

        $file = File::make($fileVo->toArray());
        $root->appendNode($file);

        return ['file' => $file, 'user' => $user];
    }

    public function test_validation_fail_not_owner_file(): void
    {
        ['file' => $file] = $this->makeTree();

        $request = FavoriteRequest::create('/file/favorite', 'PATCH');
        $request->setUserResolver(fn() => User::factory()->create());

        $validator = Validator::make(['id' => $file->id], $request->rules());

        $this->assertTrue($request->authorize());
        $this->assertTrue($validator->fails());
        $this->assertEquals(['id' => ['Invalid file ID ' . $file->id . ' for auth user.']], $validator->errors()->getMessages());
    }

    public function test_validation_fail_not_auth_user(): void
    {
        $request = FavoriteRequest::create('/file/favorite', 'PATCH');
        $this->assertFalse($request->authorize());
    }

    public function test_validation_fail_id_id_empty(): void
    {
        $request = FavoriteRequest::create('/file/favorite', 'PATCH');
        $request->setUserResolver(fn() => User::factory()->create());

        $validator = Validator::make([], $request->rules());

        $this->assertTrue($request->authorize());
        $this->assertTrue($validator->fails());
        $this->assertEquals(['id' => ['The id field is required.']], $validator->errors()->getMessages());
    }
}
