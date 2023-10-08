<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\FilesDestroyServiceInterface;
use App\Dto\DestroyFileFromStorageDto;
use App\Dto\FileIdsDto;
use App\Dto\FilesListFilterDto;
use App\Enums\FlashMessagesEnum;
use App\Http\Requests\FilesActionTrashRequest;
use App\Http\Requests\FilesListFilterRequest;
use App\Http\Resources\FileInTrashResource;
use App\Jobs\DeleteFileFromStorageJob;
use App\Models\File;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Response;
use Inertia\ResponseFactory;

class FileTrashController extends Controller
{
    public function index(FilesListFilterRequest $request): Response|ResponseFactory
    {
        $dto = new FilesListFilterDto(...$request->validated());
        $query = File::filesInTrash($request->user(), $dto);

        $files = $query->paginate(config('app.my_trash.per_page'))
            ->withQueryString();

        $fileResourceCollection = FileInTrashResource::collection($files);

        return inertia('MyTrash', [
            'files' => $fileResourceCollection,
        ]);
    }

    public function restore(FilesActionTrashRequest $request): RedirectResponse
    {
        $dto = new FileIdsDto(...$request->validated());
        $files = $dto->all
            ? File::filesInTrash($request->user())->get()
            : $request->requestFiles;

        $policyException = [];
        $restoredCount = 0;

        foreach ($files as $file) {
            try {
                $this->authorize('restore', $file);
                $file->restore();
                $restoredCount++;
            } catch (AuthorizationException $exception) {
                $policyException[] = $exception->getMessage();
            }
        }

        $response = to_route('trash.index');

        if ($policyException) {
            $response->with(FlashMessagesEnum::WARNING->value, $policyException);
        }

        return $response
            ->with(
                FlashMessagesEnum::SUCCESS->value,
                $restoredCount . Str::plural(' file', $restoredCount) . ' restored successfully'
            );
    }

    public function destroy(FilesActionTrashRequest $request, FilesDestroyServiceInterface $filesDestroy): RedirectResponse
    {
        $dto = new FileIdsDto(...$request->validated());
        $files = $dto->all
            ? File::filesInTrash($request->user())->get()
            : $request->requestFiles;

        $destroyedCollection = $filesDestroy->destroy($files)
            ->each(fn(DestroyFileFromStorageDto $destroyDto) => DeleteFileFromStorageJob::dispatch($destroyDto));

        return to_route('trash.index')->with(
            FlashMessagesEnum::SUCCESS->value,
            $destroyedCollection->count() . Str::plural(' file', $destroyedCollection->count()) . ' destroyed successfully'
        );
    }
}
