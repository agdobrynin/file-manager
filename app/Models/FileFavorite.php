<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FileFavorite
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $file_id
 * @property int $user_id
 * @method static \Illuminate\Database\Eloquent\Builder|FileFavorite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileFavorite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileFavorite query()
 * @method static \Illuminate\Database\Eloquent\Builder|FileFavorite whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileFavorite whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileFavorite whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileFavorite whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileFavorite whereUserId($value)
 * @mixin \Eloquent
 */
class FileFavorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
    ];
}
