<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\FilesDestroyServiceInterface;
use App\Dto\DestroyFileFromStorageDto;
use App\Dto\FilesIdDto;
use App\Dto\FilesListFilterDto;
use App\Enums\FlashMessagesEnum;
use App\Http\Requests\FilesActionTrashRequest;
use App\Http\Requests\FilesListRequest;
use App\Http\Resources\FileInTrashResource;
use App\Jobs\DeleteFileFromStorageJob;
use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Response;
use Inertia\ResponseFactory;

class FileTrashController extends Controller
{
    public function index(FilesListRequest $request): Response|ResponseFactory
    {
        $dto = new FilesListFilterDto(...$request->validated());

        /** @var Builder $query */
        $query = File::filesInTrash($request->user(), $dto);

        $files = $query->paginate(config('app.my_files.per_page'))
            ->withQueryString();

        $fileResourceCollection = FileInTrashResource::collection($files);

        return inertia('MyTrash', [
            'files' => $fileResourceCollection,
        ]);
    }

    public function restore(FilesActionTrashRequest $request): RedirectResponse
    {
        $dto = new FilesIdDto(...$request->validated());
        $children = $this->filesInTrashById($dto, $request->user());

        $policyException = [];
        $restoredCount = 0;

        foreach ($children as $file) {
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

    public function destroy(
        FilesActionTrashRequest      $request,
        FilesDestroyServiceInterface $filesDestroy
    ): RedirectResponse
    {
        $dto = new FilesIdDto(...$request->validated());
        $children = $this->filesInTrashById($dto, $request->user());

        $destroyedCollection = $filesDestroy->destroy($children)
            ->each(fn(DestroyFileFromStorageDto $destroyDto) => DeleteFileFromStorageJob::dispatch($destroyDto));

        return to_route('trash.index')->with(
            FlashMessagesEnum::SUCCESS->value,
            $destroyedCollection->count() . Str::plural(' file', $destroyedCollection->count()) . ' destroyed successfully'
        );
    }

    /**
     * @return Collection<File>
     */
    protected function filesInTrashById(FilesIdDto $dto, User $user): Collection
    {
        /** @var Builder $query */
        $query = File::filesInTrash($user);

        return $dto->all ? $query->get() : $query->whereIn('id', $dto->ids)->get();
    }
}
