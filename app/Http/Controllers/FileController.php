<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\UploadTreeFilesServiceInterface;
use App\Dto\FavoriteIdDto;
use App\Dto\FilesIdDto;
use App\Dto\FilesListFilterDto;
use App\Dto\ShareFilesDto;
use App\Enums\FlashMessagesEnum;
use App\Http\Requests\FavoriteRequest;
use App\Http\Requests\FilesActionRequest;
use App\Http\Requests\FilesListRequest;
use App\Http\Requests\FileUploadRequest;
use App\Http\Requests\ShareFilesRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Resources\FileAncestorsResource;
use App\Http\Resources\FileResource;
use App\Jobs\MoveFileToCloud;
use App\Models\File;
use App\Models\FileFavorite;
use App\Models\FileShare;
use App\Services\MakeDownloadFiles;
use App\VO\FileFavoriteVO;
use App\VO\FileFolderVO;
use App\VO\FileShareVO;
use App\VO\UploadFilesVO;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class FileController extends Controller
{
    public function index(FilesListRequest $request): Response
    {
        $parentFolder = $request->parentFolder ?: File::rootFolderByUser($request->user());
        $this->authorize('view', $parentFolder);

        $dto = new FilesListFilterDto(...$request->validated());

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

        $vo = new UploadFilesVO(...$request->validated());
        $files = $filesService->upload($parentFolder, $vo->tree);

        foreach ($files as $file) {
            MoveFileToCloud::dispatch($file);
        }

        return to_route('file.index', ['parentFolder' => $parentFolder]);
    }

    public function destroy(FilesActionRequest $request): RedirectResponse
    {
        $dto = new FilesIdDto(...$request->validated());
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
    public function download(FilesActionRequest $request, MakeDownloadFiles $downloadFiles): BinaryFileResponse
    {
        $dto = new FilesIdDto(...$request->validated());
        $files = $dto->all
            ? $request->parentFolder->children()->get()
            : $request->requestFiles;

        $downloadDto = $downloadFiles->handle($files);

        return \response()->download($downloadDto->storagePath, $downloadDto->fileName)->deleteFileAfterSend();
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
        $dto = new ShareFilesDto(...$request->validated());

        if ($request->shareToUser) {
            $files = $dto->all
                ? $request->parentFolder->children()->get()
                : $request->requestFiles;

            /** @var \Illuminate\Database\Eloquent\Collection<FileShare> $filesShare */
            $filesShare = FileShare::fileShareByFileOwnerAndFile($request->user(), $files)
                ->get()
                ->keyBy('file.id');
            /** @var Collection $insertData */
            $insertData = $files->reject(fn(File $file) => $filesShare->has($file->id))
                ->reduce(
                    fn(Collection $c, File $file) => $c->add((new FileShareVO($request->shareToUser, $file))->toArray()),
                    collect()
                );

            FileShare::insert($insertData->toArray());
            // TODO send email with notification through QUEUE.
        }

        return back()->with(FlashMessagesEnum::SUCCESS->value, 'Selected files will be shared if user with email exist.');
    }
}
