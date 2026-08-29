<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Post extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'featured_image',
        'is_published',
        'published_at',
    ];

    /**
     * The attributes that should be translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'title',
        'slug',
        'content',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Get the user that owns the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the post's localized slug for the current locale.
     */
    public function getLocalizedSlugAttribute(): string
    {
        return $this->getTranslation('slug', app()->getLocale());
    }

    /**
     * Get the URL for the post in the current locale.
     */
    public function getUrlAttribute(): string
    {
        return localized_route('posts.show', $this->getLocalizedSlugAttribute());
    }

    public function getFeaturedImageUrlAttribute(): string
    {
        $url = (string) $this->featured_image;
        $host = parse_url($url, PHP_URL_HOST);

        if ($url === '' || in_array($host, ['via.placeholder.com', 'placeholder.com'], true)) {
            return asset('images/post-placeholder.svg');
        }

        return $url;
    }

    /**
     * Scope a query to only include published posts.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to order by latest published.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('published_at', 'desc');
    }
}
