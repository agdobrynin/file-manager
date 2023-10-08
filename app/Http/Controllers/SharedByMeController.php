<?php

namespace App\Http\Controllers;

use App\Dto\FileIdsDto;
use App\Dto\FilesListFilterDto;
use App\Enums\FlashMessagesEnum;
use App\Http\Requests\FileShareActionRequest;
use App\Http\Requests\FilesListFilterRequest;
use App\Http\Resources\FileShareResource;
use App\Models\FileShare;
use App\Services\MakeDownloadFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Response;
use Inertia\ResponseFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SharedByMeController extends Controller
{
    public function index(FilesListFilterRequest $request): Response|ResponseFactory
    {
        $dto = new FilesListFilterDto(...$request->validated());
        $query = FileShare::fileShareByUser($request->user(), $dto);

        $files = $query->paginate(config('app.share_by_me.per_page'))
            ->withQueryString();

        $fileResourceCollection = FileShareResource::collection($files);

        return inertia('SharedByMe', ['files' => $fileResourceCollection]);
    }

    public function unshare(FileShareActionRequest $request): RedirectResponse
    {
        $dto = new FileIdsDto(...$request->validated());
        $ids = $dto->all
            ? FileShare::fileShareByFileOwner($request->user())->pluck('id')
            : $dto->ids;

        $deleteCount = FileShare::destroy($ids);

        return to_route('share_by_me.index')
            ->with(
                FlashMessagesEnum::INFO->value,
                $deleteCount . ' ' . Str::plural('file', $deleteCount) . ' disabled from sharing'
            );
    }

    /**
     * @throws Throwable
     */
    public function download(FileShareActionRequest $request, MakeDownloadFiles $downloadFiles): BinaryFileResponse
    {
        $dto = new FileIdsDto(...$request->validated());
        $fileShares = FileShare::fileShareByFileOwner($request->user())->with('file');

        if (!$dto->all) {
            $fileShares = $fileShares->whereIn('id', $dto->ids);
        }

        $downloadDto = $downloadFiles->handle($fileShares->get()->pluck('file'));

        return \response()->download($downloadDto->storagePath, $downloadDto->fileName)
            ->deleteFileAfterSend();
    }
}
