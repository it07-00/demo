<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class OperationResponsibility extends Model
{
    protected $fillable = [
        'no',
        'phase',
        'name',
    ];
}
