<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'location',
        'website_url',
        'linkedin_url',
        'email',
        'size',
        'founded_year',
        'mission',
        'culture',
        'logo_path',
    ];

    protected $casts = [
        'founded_year' => 'integer',
    ];

    protected $appends = ['logo_url'];

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function recruiters(): HasMany
    {
        return $this->hasMany(User::class)->where('is_recruiter', true);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        // Return the public API endpoint URL for the company logo
        return url('/api/companies/' . $this->slug . '/logo');
    }

    public static function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        return $slug;
    }
}
