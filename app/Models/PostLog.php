<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\PostLog
 *
 * @property int $id
 * @property int $image_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|PostLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PostLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PostLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|PostLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PostLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PostLog whereImageId($value)
 * @mixin \Eloquent
 */
class PostLog extends Model
{
    use HasFactory;

    /**
     * The name of the "updated at" column.
     *
     * @var string|null
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'image_id',
    ];
}
