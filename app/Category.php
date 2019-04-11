<?php

namespace App;

use App\Traits\FormatsDates;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static create(array $array)
 * @method static orderBy(string $string)
 */
class Category extends Model
{
    use FormatsDates;

    /**
     * Don't auto-apply mass assignment protection.
     *
     * @var array
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'created_by',
        'updated_by'
    ];

    /**
     * The relationships to always eager-load.
     *
     * @var array
     */
    protected $with = ['creator'];

    /**
     * Get the route key name for Laravel.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get a string path for the category.
     *
     * @return string
     */
    public function path()
    {
        return "/categories/{$this->slug}";
    }

    /**
     * A channel consists of threads.
     *
     * @return HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * A post is created by a particular user.
     *
     * @return BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * A post is updated by a particular user.
     *
     * @return BelongsTo
     */
    public function updator()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
