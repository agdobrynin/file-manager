<?php

namespace App\Models;

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
 * @method static Builder fileShareForUserAndFile(User $user, Collection $files)
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

    public function scopeFileShareForUserAndFile(Builder $builder, User $user, Collection $files): Builder
    {
        $filesIds = $files->pluck('id')->toArray();

        return $builder
            ->where('for_user_id', $user->getAuthIdentifier())
            ->whereIn('file_id', $filesIds);
    }
}
