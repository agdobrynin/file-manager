<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\UploadTreeFilesServiceInterface;
use App\Dto\ErrorMessageDto;
use App\Dto\FavoriteIdDto;
use App\Dto\FileIdsDto;
use App\Dto\MyFilesListFilterDto;
use App\Dto\ShareFileIdsToUserDto;
use App\Enums\FlashMessagesEnum;
use App\Http\Requests\FavoriteRequest;
use App\Http\Requests\FileUploadRequest;
use App\Http\Requests\MyFilesActionRequest;
use App\Http\Requests\MyFilesListRequest;
use App\Http\Requests\ShareFilesRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Resources\FileAncestorsResource;
use App\Http\Resources\FileResource;
use App\Jobs\MoveFileToCloud;
use App\Models\File;
use App\Models\FileFavorite;
use App\Models\FileShare;
use App\Services\MakeDownloadFilesService;
use App\VO\DownloadFileVO;
use App\VO\FileFavoriteVO;
use App\VO\FileFolderVO;
use App\VO\FileShareVO;
use App\VO\UploadFilesVO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class FileController extends Controller
{
    public function index(MyFilesListRequest $request): Response
    {
        $parentFolder = $request->parentFolder ?: File::rootFolderByUser($request->user());
        $this->authorize('view', $parentFolder);

        $dto = new MyFilesListFilterDto(...$request->validated());

        $query = File::filesList($request->user(), $dto, $parentFolder);

        $files = $query->paginate(config('app.my_files.per_page'))
            ->withQueryString();

        $fileResourceCollection = FileResource::collection($files);
        $ancestors = FileAncestorsResource::collection([...$parentFolder->ancestors, $parentFolder]);

        return inertia(
            'MyFiles', [
            'parentId' => $parentFolder->id,
            'files' => $fileResourceCollection,
            'ancestors' => $ancestors,
        ]);
    }

    public function create(StoreFolderRequest $request): RedirectResponse
    {
        $parentFolder = $request->parentFolder ?: File::rootFolderByUser($request->user());
        $this->authorize('create', $parentFolder);
        $newFolder = new FileFolderVO(...$request->validated());
        /** @var File $file */
        $file = File::query()->make($newFolder->toArray());

        $file->appendToNode($parentFolder)->save();

        return to_route('file.index', ['parentFolder' => $parentFolder]);
    }

    /**
     * @throws Throwable
     */
    public function upload(FileUploadRequest $request, UploadTreeFilesServiceInterface $filesService): RedirectResponse
    {
        $parentFolder = $request->parentFolder ?: File::rootFolderByUser($request->user());
        $this->authorize('create', $parentFolder);

        try {
            $vo = new UploadFilesVO(...$request->validated());
            $files = $filesService->upload($parentFolder, $vo->tree);
            $flash = [
                FlashMessagesEnum::SUCCESS->value,
                'Upload ' . $files->count() . ' ' . Str::plural('file', $files->count())
            ];

            foreach ($files as $file) {
                MoveFileToCloud::dispatch($file);
            }
        } catch (\Throwable $exception) {
            $flash = [FlashMessagesEnum::ERROR->value, 'Upload files error: ' . $exception->getMessage()];
        }

        return to_route('file.index', ['parentFolder' => $parentFolder])
            ->with(...$flash);
    }

    public function destroy(MyFilesActionRequest $request): RedirectResponse
    {
        $dto = new FileIdsDto(...$request->validated());
        $children = $dto->all
            ? $request->parentFolder->children()->get()
            : $request->requestFiles;

        $children->each(function (File $file) {
            $this->authorize('delete', $file);
            $file->deleteQuietly();
        });

        return to_route('file.index', ['parentFolder' => $request->parentFolder]);
    }

    /**
     * @throws Throwable
     */
    public function download(MyFilesActionRequest $request, MakeDownloadFilesService $downloadFilesService): BinaryFileResponse|JsonResponse
    {
        $dto = new FileIdsDto(...$request->validated());
        $files = $dto->all
            ? $request->parentFolder->children()->get()
            : $request->requestFiles;

        try {
            $downloadFile = $downloadFilesService->handle($files);
            $downloadFileVO = new DownloadFileVO(
                files: $files,
                downloadFile: $downloadFile,
                defaultFileName: 'My files'
            );
        } catch (Throwable $throwable) {
            $errorMessageDto = new ErrorMessageDto(message: $throwable->getMessage());

            return \response()
                ->json($errorMessageDto, 400);
        }

        return response()->download($downloadFileVO->downloadFile, $downloadFileVO->fileName)
            ->deleteFileAfterSend();
    }

    public function favorite(FavoriteRequest $request): RedirectResponse
    {
        $dto = new FavoriteIdDto(...$request->validated());

        $favorite = new FileFavoriteVO($dto->id, Auth::id());
        $favorite = FileFavorite::firstOrCreate($favorite->toArray());
        $flash = [FlashMessagesEnum::SUCCESS->value, 'File added to favorites'];

        if (false === $favorite->wasRecentlyCreated) {
            $favorite->delete();
            $flash = [FlashMessagesEnum::INFO->value, 'File delete from favorites'];
        }

        return back()->with(...$flash);
    }

    public function share(ShareFilesRequest $request): RedirectResponse
    {
        $dto = new ShareFileIdsToUserDto(...$request->validated());

        if ($request->shareToUser) {
            $files = $dto->all
                ? $request->parentFolder->children()->get()
                : $request->requestFiles;

            /** @var \Illuminate\Database\Eloquent\Collection<FileShare> $filesShare */
            $filesShare = FileShare::fileShareForUserByFile($request->shareToUser, $files)
                ->get()
                ->keyBy('file_id');
            /** @var Collection<File> $shareFiles */
            $shareFiles = $files->reject(fn(File $file) => $filesShare->has($file->id));
            /** @var Collection<FileShareVO> $insertData */
            $insertData = $shareFiles->reduce(
                fn(Collection $c, File $file) => $c->add((new FileShareVO($request->shareToUser, $file))->toArray()),
                collect()
            );

            FileShare::insert($insertData->toArray());

            $notify = new \App\Notifications\FileShare(
                files: $files,
                formUser: $request->user()
            );
            $request->shareToUser->notify($notify);
        }

        return back()->with(FlashMessagesEnum::SUCCESS->value, 'Selected files will be shared if user with email exist.');
    }
}
