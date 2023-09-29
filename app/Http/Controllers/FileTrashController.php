<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\FilesIdDto;
use App\Dto\FilesListFilterDto;
use App\Enums\FlashMessagesEnum;
use App\Http\Requests\FilesActionTrashRequest;
use App\Http\Requests\FilesListRequest;
use App\Http\Resources\FileResource;
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

        $fileResourceCollection = FileResource::collection($files);

        return inertia('MyTrash', [
            'files' => $fileResourceCollection,
        ]);
    }

    public function restore(FilesActionTrashRequest $request): RedirectResponse
    {
        $dto = new FilesIdDto(...$request->validated());
        $children = $this->children($dto, $request->user());

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

    public function destroy(FilesActionTrashRequest $request): RedirectResponse
    {
        $dto = new FilesIdDto(...$request->validated());
        $children = $this->children($dto, $request->user());

        $destroyedCount = 0;
        $policyException = [];

        foreach ($children as $file) {
            try {
                $this->authorize('forceDelete', $file);
                // check is folder.
                // get storage, storage_path and dispatch job delete from storage
                // $file->forceDelete();
            } catch (AuthorizationException $exception) {
                $policyException[] = $exception->getMessage();
            }
        }

        $response = to_route('trash.index');

        if ($policyException) {
            $response->with(FlashMessagesEnum::WARNING->value, $policyException);
        }

        return $response->with(
            FlashMessagesEnum::SUCCESS->value,
            $destroyedCount . Str::plural(' file', $destroyedCount) . ' destroyed successfully'
        );
    }

    /**
     * @return Collection<File>
     */
    protected function children(FilesIdDto $dto, User $user): Collection
    {
        /** @var Builder $query */
        $query = File::filesInTrash($user);

        return $dto->all ? $query->get() : $query->whereIn('id', $dto->ids)->get();
    }
}
