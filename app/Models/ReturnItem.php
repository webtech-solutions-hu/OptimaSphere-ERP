<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    protected $fillable = [
        'return_id', 'product_id', 'quantity_requested', 'quantity_received',
        'quantity_approved', 'quantity_restocked', 'unit_price', 'refund_amount',
        'condition', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'decimal:2',
            'quantity_received' => 'decimal:2',
            'quantity_approved' => 'decimal:2',
            'quantity_restocked' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'refund_amount' => 'decimal:2',
        ];
    }

    public function return(): BelongsTo
    {
        return $this->belongsTo(ReturnModel::class, 'return_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
