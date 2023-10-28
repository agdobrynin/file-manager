<?php

namespace App\Http\Controllers;

use App\Dto\ErrorMessageDto;
use App\Dto\FileIdsDto;
use App\Dto\FilesListFilterDto;
use App\Enums\FlashMessagesEnum;
use App\Http\Requests\FileShareActionRequest;
use App\Http\Requests\FilesListFilterRequest;
use App\Http\Resources\FileShareResource;
use App\Models\FileShare;
use App\Services\MakeDownloadFilesService;
use App\VO\DownloadFileVO;
use Illuminate\Http\JsonResponse;
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
                $deleteCount.' '.Str::plural('file', $deleteCount).' disabled from sharing'
            );
    }

    /**
     * @throws Throwable
     */
    public function download(FileShareActionRequest $request, MakeDownloadFilesService $downloadFilesService): BinaryFileResponse|JsonResponse
    {
        $dto = new FileIdsDto(...$request->validated());
        $fileShares = FileShare::fileShareByFileOwner($request->user())->with('file');

        if (! $dto->all) {
            $fileShares = $fileShares->whereIn('id', $dto->ids);
        }

        try {
            $files = $fileShares->get()->pluck('file');
            $downloadFile = $downloadFilesService->handle($files);
            $downloadFileVO = new DownloadFileVO(
                files: $files,
                downloadFile: $downloadFile,
                defaultFileName: 'Files share by me'
            );
        } catch (Throwable $throwable) {
            $errorMessageDto = new ErrorMessageDto(message: $throwable->getMessage());

            return \response()
                ->json($errorMessageDto, 400);
        }

        return \response()->download($downloadFileVO->downloadFile, $downloadFileVO->fileName)
            ->deleteFileAfterSend();
    }
}
