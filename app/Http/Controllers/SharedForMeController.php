<?php

namespace App\Http\Controllers;

use App\Dto\FilesIdDto;
use App\Dto\FilesListFilterDto;
use App\Http\Requests\FileShareActionRequest;
use App\Http\Requests\FilesListFilterRequest;
use App\Http\Resources\FileShareResource;
use App\Models\FileShare;
use App\Services\MakeDownloadFiles;
use Inertia\Response;
use Inertia\ResponseFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SharedForMeController extends Controller
{
    public function index(FilesListFilterRequest $request): Response|ResponseFactory
    {
        $dto = new FilesListFilterDto(...$request->validated());
        $query = FileShare::fileShareForUserWithFilter($request->user(), $dto);
        $files = $query->paginate(config('app.shared_for_me.per_page'))
            ->withQueryString();
        $fileResourceCollection = FileShareResource::collection($files);

        return inertia('SharedForMe', ['files' => $fileResourceCollection]);
    }

    /**
     * @throws Throwable
     */
    public function download(FileShareActionRequest $request, MakeDownloadFiles $downloadFiles): BinaryFileResponse
    {
        $dto = new FilesIdDto(...$request->validated());
        $fileShares = FileShare::fileShareForUser($request->user())->with('file');

        if (!$dto->all) {
            $fileShares = $fileShares->whereIn('id', $dto->ids);
        }

        $downloadDto = $downloadFiles->handle($fileShares->get()->pluck('file'));

        return response()->download($downloadDto->storagePath, $downloadDto->fileName)
            ->deleteFileAfterSend();
    }
}
