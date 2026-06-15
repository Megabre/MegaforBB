<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessageReply extends Model
{
    protected $table = 'contact_message_replies';

    public $timestamps = false;

    protected $fillable = [
        'contact_message_id',
        'reply_body',
        'replied_by_user_id',
        'email_sent',
        'created_at',
    ];

    protected $casts = [
        'email_sent' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function contactMessage(): BelongsTo
    {
        return $this->belongsTo(ContactMessage::class, 'contact_message_id');
    }

    public function repliedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by_user_id');
    }
}
