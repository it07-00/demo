<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class OperationCrmCustomer extends Model
{
    protected $fillable = [
        'name',
        'type',
        'stage',
        'stage_idx',
        'relationship',
        'contact_name',
        'contact_role',
        'contact_phone',
        'contact_email',
        'source',
        'priority',
        'owner_name',
        'revenue_monthly',
        'last_meeting',
        'next_meeting',
        'next_action',
        'active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_meeting' => 'date',
            'next_meeting' => 'date',
            'active' => 'boolean',
            'notes' => 'array',
        ];
    }
}
