<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacationApproval extends Model
{
    protected $fillable = [
        'vacation_request_id',
        'approver_id',
        'action',
        'comment',
    ];

    public function vacationRequest(): BelongsTo
    {
        return $this->belongsTo(VacationRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
