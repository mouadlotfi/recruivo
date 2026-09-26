<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruiterNoteTemplate extends Model
{
    protected $fillable = ['recruiter_id', 'name', 'body'];

    public function recruiter()
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }
}
