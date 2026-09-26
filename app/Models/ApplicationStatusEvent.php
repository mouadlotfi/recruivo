<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationStatusEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'application_id',
        'changed_by_user_id',
        'from_status',
        'to_status',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
