<?php

namespace App\Models;

use App\Dto\FilesListFilterDto;
use App\Enums\DiskEnum;
use App\Traits\HasCreatorAndUpdater;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Kalnoy\Nestedset\NodeTrait;

/**
 * App\Models\File
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property DiskEnum $disk
 * @property string|null $path
 * @property string|null $storage_path
 * @property int $_lft
 * @property int $_rgt
 * @property int|null $parent_id
 * @property bool $is_folder
 * @property string|null $mime
 * @property int|null $size
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $created_by
 * @property int $updated_by
 * @property-read \Kalnoy\Nestedset\Collection<int, File> $children
 * @property-read int|null $children_count
 * @property-read \App\Models\FileFavorite|null $favorite
 * @property-read File|null $parent
 * @property-read \App\Models\User|null $user
 * @method static \Kalnoy\Nestedset\Collection<int, static> all($columns = ['*'])
 * @method static \Kalnoy\Nestedset\QueryBuilder|File ancestorsAndSelf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder|File ancestorsOf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder|File applyNestedSetScope(?string $table = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File countErrors()
 * @method static \Kalnoy\Nestedset\QueryBuilder|File d()
 * @method static \Kalnoy\Nestedset\QueryBuilder|File defaultOrder(string $dir = 'asc')
 * @method static \Kalnoy\Nestedset\QueryBuilder|File descendantsAndSelf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder|File descendantsOf($id, array $columns = [], $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File fixSubtree($root)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File fixTree($root = null)
 * @method static \Kalnoy\Nestedset\Collection<int, static> get($columns = ['*'])
 * @method static \Kalnoy\Nestedset\QueryBuilder|File getNodeData($id, $required = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File getPlainNodeData($id, $required = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File getTotalErrors()
 * @method static \Kalnoy\Nestedset\QueryBuilder|File hasChildren()
 * @method static \Kalnoy\Nestedset\QueryBuilder|File hasParent()
 * @method static \Kalnoy\Nestedset\QueryBuilder|File isBroken()
 * @method static \Kalnoy\Nestedset\QueryBuilder|File leaves(array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder|File makeGap(int $cut, int $height)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File moveNode($key, $position)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File newModelQuery()
 * @method static \Kalnoy\Nestedset\QueryBuilder|File newQuery()
 * @method static Builder|File onlyTrashed()
 * @method static \Kalnoy\Nestedset\QueryBuilder|File orWhereAncestorOf(bool $id, bool $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File orWhereDescendantOf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File orWhereNodeBetween($values)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File orWhereNotDescendantOf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File query()
 * @method static \Kalnoy\Nestedset\QueryBuilder|File rebuildSubtree($root, array $data, $delete = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File rebuildTree(array $data, $delete = false, $root = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File reversed()
 * @method static \Kalnoy\Nestedset\QueryBuilder|File root(array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereAncestorOf($id, $andSelf = false, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereAncestorOrSelf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereCreatedAt($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereCreatedBy($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereDeletedAt($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereDescendantOf($id, $boolean = 'and', $not = false, $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereDescendantOrSelf(string $id, string $boolean = 'and', string $not = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereDisk($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereId($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereIsAfter($id, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereIsBefore($id, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereIsFolder($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereIsLeaf()
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereIsRoot()
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereLft($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereMime($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereName($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereNodeBetween($values, $boolean = 'and', $not = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereNotDescendantOf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereParentId($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File wherePath($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereRgt($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereSize($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereStoragePath($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereUpdatedAt($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File whereUpdatedBy($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|File withDepth(string $as = 'depth')
 * @method static Builder|File withTrashed()
 * @method static \Kalnoy\Nestedset\QueryBuilder|File withoutRoot()
 * @method static Builder|File withoutTrashed()
 * @method static Builder filesList(User $user, FilesListFilterDto $dto, File $folder)
 * @method static Builder filesInTrash(User $user, ?FilesListFilterDto $dto = null)
 * @mixin \Eloquent
 */
class File extends Model
{
    use HasFactory, NodeTrait, SoftDeletes, HasCreatorAndUpdater;

    protected $fillable = [
        'name',
        'disk',
        'path',
        'storage_path',
        'is_folder',
        'mime',
        'size',
    ];

    protected $casts = [
        'is_folder' => 'boolean',
        'disk' => DiskEnum::class,
    ];

    public static function makeRootByUser(User $user): File
    {
        try {
            return self::rootFolderByUser($user);
        } catch (ModelNotFoundException $exception) {
            $file = self::make([
                'name' => $user->email,
                'is_folder' => true,
                'disk' => DiskEnum::LOCAL,
            ]);

            $file->makeRoot()->save();

            return $file;
        }
    }

    /**
     * @throws ModelNotFoundException
     */
    public static function rootFolderByUser(?User $user): File
    {
        return self::query()
            ->whereIsRoot()
            ->where('created_by', $user?->getAuthIdentifier())
            ->firstOrFail();
    }

    public static function existNames(array $names, User $user, File $parentFolder): Collection
    {
        return self::query()
            ->where('created_by', $user->getAuthIdentifier())
            ->where('parent_id', $parentFolder->id)
            ->whereNull('deleted_at')
            ->whereIn('name', $names)
            ->get();
    }

    public static function fileShareByUser(User $user, FilesListFilterDto $dto): Builder
    {
        return self::fileShareToFile($dto)
            ->whereHas(
                'user',
                fn(Builder $q) => $q->where('id', $user->getAuthIdentifier())
            );
    }

    protected static function fileShareToFile(FilesListFilterDto $dto): Builder
    {
        return self::query()
            ->select('files.*')
            ->when(
                $dto->search,
                fn(Builder $q) => $q->where('name', 'like', "%{$dto->search}%")
            )
            ->with(['user'])
            ->leftJoin('file_shares', 'file_shares.file_id', 'files.id')
            ->orderBy('is_folder', 'desc')
            ->orderBy('file_shares.created_at', 'desc');
    }

    public static function fileShareForUser(User $user, FilesListFilterDto $dto): Builder
    {
        return self::fileShareToFile($dto)
            ->whereHas(
                'fileShare',
                fn(Builder $q) => $q->where('for_user_id', $user->getAuthIdentifier())
            );
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (File $model) {
            if (!$model->isRoot()) {
                $separator = str_ends_with($model->parent->path ?? '', DIRECTORY_SEPARATOR)
                    ? ''
                    : DIRECTORY_SEPARATOR;

                $model->path = $model->parent->path . $separator . $model->name;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function favorite(): HasOne
    {
        return $this->hasOne(FileFavorite::class);
    }

    public function fileShare(): HasMany
    {
        return $this->hasMany(FileShare::class);
    }

    public function isOwnedByUser(?User $user): bool
    {
        return $this->created_by === $user?->getAuthIdentifier();
    }

    public function isFolder(): bool
    {
        return (bool)$this->is_folder;
    }

    public function scopeFilesList(Builder $builder, User $user, FilesListFilterDto $dto, File $folder): Builder
    {
        if ($dto->search) {
            $builder->where('name', 'like', "%$dto->search%");
        } else {
            $builder->where('parent_id', $folder->id);
        }

        return $builder->whereNotNull('parent_id')
            ->with(['favorite'])
            ->when($dto->onlyFavorites, fn() => $builder->whereHas('favorite'))
            ->where('created_by', '=', $user->getAuthIdentifier())
            ->with(['favorite'])
            ->orderBy('is_folder', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('files.id', 'desc');
    }

    public function scopeFilesInTrash(Builder $builder, User $user, ?FilesListFilterDto $dto = null): Builder
    {
        $builder->onlyTrashed();

        if ($dto && $dto->search) {
            $builder->where('name', 'like', "%$dto->search%");
        }

        return $builder->where('created_by', '=', $user->getAuthIdentifier())
            ->orderBy('is_folder', 'desc')
            ->orderBy('deleted_at', 'desc')
            ->orderBy('files.id', 'desc');
    }

    protected function owner(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attr) => $attr['created_by'] === Auth::id() ? 'me' : $this->user->name
        );
    }

    protected function disk(): Attribute
    {
        return Attribute::make(
            set: static fn(DiskEnum $value) => $value->value,
        );
    }
}
