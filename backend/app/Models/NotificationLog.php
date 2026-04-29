<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'user_id',
        'channel',
        'type',
        'message',
        'metadata',
        'sent_successfully',
        'error_message',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_successfully' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
