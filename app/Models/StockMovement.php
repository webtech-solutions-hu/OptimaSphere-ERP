<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'warehouse_id',
        'product_id',
        'type',
        'quantity',
        'unit_cost',
        'total_cost',
        'balance_before',
        'balance_after',
        'related_document_type',
        'related_document_id',
        'created_by',
        'notes',
        'movement_date',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'movement_date' => 'datetime',
        ];
    }

    /**
     * Boot method to auto-generate reference
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($movement) {
            if (empty($movement->reference)) {
                $movement->reference = self::generateReference();
            }
        });
    }

    /**
     * Generate unique reference number
     */
    private static function generateReference(): string
    {
        $prefix = 'SM';
        $date = now()->format('Ymd');
        $lastMovement = self::where('reference', 'like', $prefix . $date . '%')
            ->latest('id')
            ->first();

        if ($lastMovement) {
            $lastNumber = intval(substr($lastMovement->reference, -4));
            $number = $lastNumber + 1;
        } else {
            $number = 1;
        }

        return $prefix . $date . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the related document
     */
    public function relatedDocument()
    {
        if (!$this->related_document_type || !$this->related_document_id) {
            return null;
        }

        $modelClass = 'App\\Models\\' . str_replace('_', '', ucwords($this->related_document_type, '_'));

        if (class_exists($modelClass)) {
            return $modelClass::find($this->related_document_id);
        }

        return null;
    }
}
