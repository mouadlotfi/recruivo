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
    ];

    protected $casts = [
        'status' => JobStatus::class,
        'published_at' => 'datetime',
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

    public function scopePublished(Builder $builder): Builder
    {
        return $builder->where('status', JobStatus::Published->value)->whereNotNull('published_at');
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
