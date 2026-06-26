<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class OperationProject extends Model
{
    protected $fillable = [
        'external_id',
        'code',
        'name',
        'customer',
        'customer_type',
        'branch',
        'product',
        'method',
        'policy',
        'unit_price',
        'recruit_status',
        'manager_external_id',
        'manager_name',
        'unassigned',
        'team',
        'status',
        'demand',
        'actual',
        'shortage',
        'progress',
        'contract_start',
        'contract_end',
        'paused_days',
        'reported_today',
        'docs',
    ];

    protected function casts(): array
    {
        return [
            'unassigned' => 'boolean',
            'team' => 'array',
            'contract_start' => 'date',
            'contract_end' => 'date',
            'reported_today' => 'boolean',
            'docs' => 'array',
        ];
    }

    public function recruitmentReports(): HasMany
    {
        return $this->hasMany(OperationRecruitmentReport::class);
    }
}
