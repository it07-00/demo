<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class OperationReceivable extends Model
{
    protected $fillable = [
        'external_id',
        'customer',
        'amount',
        'due_date',
        'state',
        'note',
        'paid',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid' => 'boolean',
        ];
    }
}
