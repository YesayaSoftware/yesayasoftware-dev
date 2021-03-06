<?php

namespace App;

use App\Filters\PostFilters;
use App\Traits\FormatsDates;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;

/**
 * @method static limit(int $int)
 * @method static create(array $array)
 * @method static latest()
 */
class Post extends Model
{
    use FormatsDates, RecordsActivity;

    /**
     * Don't auto-apply mass assignment protection.
     *
     * @var array
     */
    protected $fillable = [
        'slug',
        'title',
        'body',
        'thumbnail',
        'comment_count',
        'visits',
        'category_id',
        'created_by',
        'updated_by',
    ];

    /**
     * The relationships to always eager-load.
     *
     * @var array
     */
    protected $with = [
        'creator',
        'updator',
        'category',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['thumbnail_path'];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($post) {
            $post->comments->each->delete();
        });

        static::created(function ($post) {
            $post->update(['slug' => $post->title]);
        });
    }

    /**
     * Get a string path for the post.
     *
     * @return string
     */
    public function path()
    {
        return "/posts/{$this->category->slug}/{$this->slug}";
    }

    /**
     * Get the path to the user's avatar.
     *
     * @return string
     */
    public function getThumbnailPathAttribute()
    {
        if (App::environment('production'))
            return $this->thumbnail ? env('AWS_URL') . $this->thumbnail : asset('img/post-thumbnail.jpg');
        else
            return asset($this->thumbnail ? '/storage/' . $this->thumbnail : 'img/post-thumbnail.jpg');
    }

    /**
     * A post belongs a category.
     *
     * @return BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * A thread may have many comments.blade.php.
     *
     * @return HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
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

    /**
     * Add a comment to the post.
     *
     * @param array $comment
     * @return Model
     */
    public function addComment($comment)
    {
        $comment = $this->comments()->create($comment);

        //event(new PostReceivedNewComment($comment));

        return $comment;
    }

    /**
     * Apply all relevant $post filters.
     *
     * @param Builder $query
     * @param PostFilters $filters
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, PostFilters $filters)
    {
        return $filters->apply($query);
    }

    /**
     * Determine if the post has been updated since the user last read it.
     *
     * @param User $user
     * @return bool
     * @throws Exception
     */
    public function hasUpdatesFor($user)
    {
        $key = $user->visitedPostCacheKey($this);

        return $this->updated_at > cache($key);
    }

    /**
     * Get the route key name.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Access the body attribute.
     *
     * @param string $body
     *
     * @return string
     */
    public function getBodyAttribute($body)
    {
        return Purify::clean($body);
    }

    /**
     * Set the proper slug attribute.
     *
     * @param string $value
     */
    public function setSlugAttribute($value)
    {
        $value = Str::slug($value);

        if (static::whereSlug($slug = $value)->exists())
            $slug = "{$slug}-{$this->id}";

        $this->attributes['slug'] = $slug;
    }
}
