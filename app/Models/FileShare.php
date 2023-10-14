<?php

namespace App\Models;

use App\Dto\FilesListFilterDto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FileShare
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $file_id
 * @property int $for_user_id
 * @method static \Illuminate\Database\Eloquent\Builder|FileShare newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileShare newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileShare query()
 * @method static \Illuminate\Database\Eloquent\Builder|FileShare whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileShare whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileShare whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileShare whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileShare whereUserId($value)
 * @method static Builder fileShareForUserByFile(User $user, Collection $files)
 * @method static Builder fileShareByUser(User $user, FilesListFilterDto $dto)
 * @method static Builder fileShareForUserWithFilter(User $user, FilesListFilterDto $dto)
 * @method static Builder fileShareByFileOwner(User $user)
 * @method static Builder fileShareForUser(User $user)
 * @method static Builder fileShareForUserOrByUser(User $user);
 * @mixin \Eloquent
 */
class FileShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'for_user_id',
        'created_at',
        'updated_at',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function forUser(): BelongsTo
    {
        return $this->belongsTo(User::class, foreignKey: 'for_user_id');
    }

    public function scopeFileShareByFileOwner(Builder $builder, User $user): Builder
    {
        return $builder->whereHas('file.user', function (Builder $b) use ($user) {
            return $b->where('id', $user->getAuthIdentifier());
        });
    }

    public function scopeFileShareByUser(Builder $builder, User $user, FilesListFilterDto $dto): Builder
    {
        return $this->fileShareByFilter($builder, $dto)
            ->with(['file', 'forUser'])
            ->whereHas('file.user', function (Builder $q) use ($user) {
                return $q->where('id', $user->getAuthIdentifier());
            });
    }

    protected function fileShareByFilter(Builder $builder, FilesListFilterDto $dto): Builder
    {
        return $builder
            ->whereHas('file')
            ->when($dto->search, function (Builder $builder) use ($dto) {
                $builder->whereHas('file',
                    function (Builder $query) use ($dto) {
                        return $query->where(
                            'name',
                            'like',
                            '%' . $dto->search . '%'
                        );
                    }
                );
            })
            ->orderBy('created_at', 'desc');
    }

    public function scopeFileShareForUser(Builder $builder, User $user): Builder
    {
        return $builder->whereHas('file')
            ->where('for_user_id', $user->getAuthIdentifier())
            ->orderBy('created_at', 'desc');
    }

    public function scopeFileShareForUserByFile(Builder $builder, User $user, Collection $files): Builder
    {
        $filesIds = $files->pluck('id')->toArray();

        return $builder
            ->whereHas('file')
            ->where('for_user_id', $user->getAuthIdentifier())
            ->whereIn('file_id', $filesIds);
    }

    public function scopeFileShareForUserOrByUser(Builder $builder, User $user): Builder
    {
        return $builder->whereHas('file.user', function (Builder $b) use($user) {
            return $b->where('id', $user->getAuthIdentifier());
        })->orWhere('for_user_id', $user->getAuthIdentifier());
    }

    public function scopeFileShareForUserWithFilter(Builder $builder, User $user, FilesListFilterDto $dto): Builder
    {
        return $this->fileShareByFilter($builder, $dto)
            ->with(['file.user', 'forUser'])
            ->where('for_user_id', $user->getAuthIdentifier());
    }
}
