<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class WeeklyTarget extends Model
{
    protected $fillable = [
        'operation_project_id',
        'year',
        'week_number',
        'week_start',
        'week_end',
        'customer_demand',
        'manager_accepted',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(OperationProject::class, 'operation_project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WeeklyTargetAssignment::class);
    }

    /**
     * Total assigned across all specialists.
     */
    public function totalAssigned(): int
    {
        return (int) $this->assignments()->sum('assigned_quantity');
    }

    /**
     * Total achieved this week (sum of all daily entries for all assignments).
     */
    public function totalAchieved(): int
    {
        return (int) DailyProgressEntry::query()
            ->whereIn('weekly_target_assignment_id', $this->assignments()->pluck('id'))
            ->sum('achieved');
    }
}
