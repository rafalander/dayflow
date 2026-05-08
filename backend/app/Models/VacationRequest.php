<?php

namespace App\Models;

use App\Support\AbsenceTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VacationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'absence_type',
        'approver_id',
        'start_date',
        'end_date',
        'status',
        'reason',
        'rejection_reason',
        'business_days',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'business_days' => 'integer',
    ];

    protected $appends = [
        'absence_type_label',
    ];

    public function getAbsenceTypeLabelAttribute(): string
    {
        return AbsenceTypes::label((string) ($this->absence_type ?? 'vacation'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(VacationApproval::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public static function calculateBusinessDays(\Carbon\Carbon $start, \Carbon\Carbon $end): int
    {
        $days = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeOverlapping($query, $startDate, $endDate, $excludeId = null)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->where('start_date', '<=', $endDate)
              ->where('end_date', '>=', $startDate);
        })->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }
}
