<?php

namespace App\Models;

use App\Enums\JobStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Job extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'recruiter_id',
        'company_id',
        'title',
        'description',
        'location',
        'salary_min',
        'salary_max',
        'status',
        'category',
        'remote_type',
        'published_at',
        'closes_at',
    ];

    protected $casts = [
        'status' => JobStatus::class,
        'published_at' => 'datetime',
        'closes_at' => 'date',
    ];

    public function recruiter()
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function savedByCandidates()
    {
        return $this->belongsToMany(User::class, 'saved_jobs')->withTimestamps();
    }

    public function isExpired(): bool
    {
        return $this->closes_at?->isBefore(today()) ?? false;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === JobStatus::Published
            && $this->published_at !== null
            && !$this->isExpired();
    }

    public function isClosingSoon(): bool
    {
        return !$this->isExpired()
            && $this->closes_at !== null
            && $this->closes_at->lte(today()->addDays(7));
    }

    public function scopeOrderByPreference(Builder $query, ?array $preferredCategories): Builder
    {
        if (!$preferredCategories) {
            return $query->latest('published_at');
        }

        $placeholders = implode(',', array_fill(0, count($preferredCategories), '?'));

        return $query
            ->orderByRaw("CASE WHEN category IN ($placeholders) THEN 0 ELSE 1 END", $preferredCategories)
            ->latest('published_at');
    }

    public function scopePublished(Builder $builder): Builder
    {
        return $builder
            ->where('status', JobStatus::Published->value)
            ->whereNotNull('published_at')
            ->where(function (Builder $query) {
                $query->whereNull('closes_at')
                    ->orWhereDate('closes_at', '>=', today());
            });
    }

    public function scopeWithSavedStateFor(Builder $query, ?User $user): Builder
    {
        if (!$user?->hasRole('Candidate')) {
            return $query;
        }

        return $query->withExists([
            'savedByCandidates as is_saved' => fn (Builder $saved) => $saved->whereKey($user->id),
        ]);
    }

    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'description' => strip_tags($this->description),
            'location' => $this->location,
            'category' => $this->category,
            'remote_type' => $this->remote_type,
            'company' => $this->company?->name,
        ];
    }
}
