<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FileShare
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $file_id
 * @property int $user_id
 * @method static \Illuminate\Database\Eloquent\Builder|FileShare newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileShare newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileShare query()
 * @method static \Illuminate\Database\Eloquent\Builder|FileShare whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileShare whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileShare whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileShare whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileShare whereUserId($value)
 * @mixin \Eloquent
 */
class FileShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
    ];
}
