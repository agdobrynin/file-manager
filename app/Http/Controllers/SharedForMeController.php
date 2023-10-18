<?php

namespace App\Http\Controllers;

use App\Dto\ErrorMessageDto;
use App\Dto\FileIdsDto;
use App\Dto\FilesListFilterDto;
use App\Http\Requests\FileShareActionRequest;
use App\Http\Requests\FilesListFilterRequest;
use App\Http\Resources\FileShareResource;
use App\Models\FileShare;
use App\Services\MakeDownloadFilesService;
use Illuminate\Http\JsonResponse;
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
        $files = $query->paginate(config('app.share_for_me.per_page'))
            ->withQueryString();
        $fileResourceCollection = FileShareResource::collection($files);

        return inertia('SharedForMe', ['files' => $fileResourceCollection]);
    }

    /**
     * @throws Throwable
     */
    public function download(FileShareActionRequest $request, MakeDownloadFilesService $downloadFilesService): BinaryFileResponse|JsonResponse
    {
        $dto = new FileIdsDto(...$request->validated());
        $fileShares = FileShare::fileShareForUser($request->user())->with('file');

        if (!$dto->all) {
            $fileShares = $fileShares->whereIn('id', $dto->ids);
        }

        try {
            $downloadDto = $downloadFilesService->handle($fileShares->get()->pluck('file'));
        } catch (Throwable $throwable) {
            $errorMessageDto = new ErrorMessageDto(message: $throwable->getMessage());

            return \response()
                ->json($errorMessageDto, 400);
        }

        return response()->download($downloadDto->storagePath, $downloadDto->fileName)
            ->deleteFileAfterSend();
    }
}
