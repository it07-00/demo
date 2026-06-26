<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class WeeklyTargetAssignment extends Model
{
    protected $fillable = [
        'weekly_target_id',
        'user_id',
        'assigned_quantity',
        'assigned_by',
    ];

    public function weeklyTarget(): BelongsTo
    {
        return $this->belongsTo(WeeklyTarget::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function dailyEntries(): HasMany
    {
        return $this->hasMany(DailyProgressEntry::class);
    }

    /**
     * Sum of achieved for this assignment in the current week.
     */
    public function totalAchieved(): int
    {
        return (int) $this->dailyEntries()->sum('achieved');
    }

    /**
     * Progress percentage: totalAchieved / assignedQuantity * 100.
     */
    public function progressPercent(): float
    {
        if ($this->assigned_quantity <= 0) {
            return 0;
        }

        return round(($this->totalAchieved() / $this->assigned_quantity) * 100, 1);
    }
}
