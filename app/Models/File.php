<?php

namespace App\Models;

use App\Dto\MyFilesFilterDto;
use App\Enums\DiskEnum;
use App\Traits\HasCreatorAndUpdater;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Kalnoy\Nestedset\NodeTrait;

class File extends Model
{
    use HasFactory, NodeTrait, SoftDeletes, HasCreatorAndUpdater;

    protected $fillable = [
        'name',
        'disk',
        'path',
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

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (File $model) {
            if (!$model->isRoot() && $model->is_folder) {
                $model->path = ($model->parent->path ? $model->parent->path . '/' : '') . $model->name;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOwnedByUser(?User $user): bool
    {
        return $this->created_by === $user?->getAuthIdentifier();
    }

    public function isFolder(): bool
    {
        return (bool)$this->is_folder;
    }

    public function scopeMyFiles(Builder $builder, User $user, MyFilesFilterDto $dto, File $folder): Builder
    {
        if ($dto->search) {
            $builder->where('name', 'like', "%$dto->search%");
        } else {
            $builder->where('parent_id', $folder->id);
        }

        return $builder->where('created_by', '=', $user->getAuthIdentifier())
            ->orderBy('is_folder', 'desc')
            ->orderBy('created_at', 'desc');
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
