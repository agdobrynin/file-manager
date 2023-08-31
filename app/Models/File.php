<?php

namespace App\Models;

use App\Dto\MyFilesFilterDto;
use App\Traits\HasCreatorAndUpdater;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\NodeTrait;

class File extends Model
{
    use HasFactory;
    use NodeTrait;
    use SoftDeletes;
    use HasCreatorAndUpdater;

    protected $fillable = [
        'name',
        'path',
        'is_folder',
        'mime',
        'size',
    ];
    protected $casts = [
        'is_folder' => 'boolean'
    ];

    /**
     * @throws ModelNotFoundException
     */
    public static function rootFolderByUser(?User $user): File
    {
        return File::query()
            ->whereIsRoot()
            ->where('created_by', $user?->getAuthIdentifier())
            ->firstOrFail();
    }

    public static function makeRootByUser(User $user): File
    {
        try {
            return static::rootFolderByUser($user);
        } catch (ModelNotFoundException $exception) {
            $file = File::make([
                'name' => $user->email,
                'is_folder' => true,
            ]);

            $file->makeRoot()->save();

            return $file;
        }
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (File $model) {
            if ($model->parent) {
                $model->path = ($model->isRoot() ? '' : $model->parent->path . '/')
                    . Str::slug($model->name);

            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function owner(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attr) => $attr['created_by'] === Auth::id() ? 'me' : $this->user->name
        );
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
            $builder->where('name', 'like', "%{$dto->search}%");
        } else {
            $builder->where('parent_id', $folder->id);
        }

        return $builder->where('created_by', '=', $user->getAuthIdentifier())
            ->orderBy('is_folder', 'desc')
            ->orderBy('created_at', 'desc');
    }
}
