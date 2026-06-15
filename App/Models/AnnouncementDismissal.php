<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementDismissal extends Model
{
    protected $table = 'announcement_dismissals';

    public $timestamps = false;

    protected $fillable = ['user_id', 'announcement_id', 'dismissed_at'];

    protected $casts = [
        'user_id' => 'integer',
        'announcement_id' => 'integer',
    ];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class, 'announcement_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
