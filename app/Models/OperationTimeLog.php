<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationTimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_operation_id',
        'operator_id',
        'log_type',
        'logged_at',
        'quantity_processed',
        'quantity_scrapped',
        'scan_method',
        'scanned_code',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
            'quantity_processed' => 'decimal:2',
            'quantity_scrapped' => 'decimal:2',
        ];
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderOperation::class, 'production_order_operation_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
