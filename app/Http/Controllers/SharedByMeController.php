<?php

namespace App\Http\Controllers;

use App\Dto\FilesIdDto;
use App\Dto\FilesListFilterDto;
use App\Enums\FlashMessagesEnum;
use App\Http\Requests\FileShareByMeRequest;
use App\Http\Requests\FilesListFilterRequest;
use App\Http\Resources\FileShareResource;
use App\Models\FileShare;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Response;
use Inertia\ResponseFactory;

class SharedByMeController extends Controller
{
    public function index(FilesListFilterRequest $request): Response|ResponseFactory
    {
        $dto = new FilesListFilterDto(...$request->validated());
        $query = FileShare::fileShareByUser($request->user(), $dto);

        $files = $query->paginate(config('app.shared_by_me.per_page'))
            ->withQueryString();

        $fileResourceCollection = FileShareResource::collection($files);

        return inertia('SharedByMe', ['files' => $fileResourceCollection]);
    }

    public function unshare(FileShareByMeRequest $request): RedirectResponse
    {
        $dto = new FilesIdDto(...$request->validated());
        $ids = $dto->all
            ? FileShare::fileShareByFileOwner($request->user())->pluck('id')
            : $dto->ids;

        $deleteCount = FileShare::destroy($ids);

        return to_route('shared_by_me.index')
            ->with(
                FlashMessagesEnum::INFO->value,
                $deleteCount . ' ' . Str::plural('file', $deleteCount) . ' disabled from sharing'
            );
    }
}
