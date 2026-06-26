<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OperationRecruitmentReport extends Model
{
    protected $fillable = [
        'operation_project_id',
        'report_date',
        'branch',
        'customer',
        'manager',
        'demand',
        'method',
        'registered',
        'interviewed',
        'passed',
        'started',
        'partner_trial',
        'rank',
        'reporter',
        'reported_at',
        'issues',
        'approved',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'approved' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(OperationProject::class, 'operation_project_id');
    }
}
