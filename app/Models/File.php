<?php

namespace App\Models;

use App\Traits\HasCreatorAndUpdater;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'parent_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOwnedByUserId(int $userId): bool
    {
        return $this->created_by === $userId;
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
}
