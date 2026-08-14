<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'headline',
        'experience',
        'education',
        'languages',
        'skills',
        'resume_path',
        'linkedin_url',
        'portfolio_url',
        'github_url',
        'website_url',
        'languages_data',
        'profile_links',
        'experiences',
        'educations',
        'preferred_categories',
    ];

    protected $casts = [
        'languages_data' => 'array',
        'profile_links' => 'array',
        'experiences' => 'array',
        'educations' => 'array',
        'preferred_categories' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
