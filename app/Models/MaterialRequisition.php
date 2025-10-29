<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialRequisition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference',
        'production_order_id',
        'warehouse_id',
        'type',
        'status',
        'required_date',
        'requested_by',
        'approved_by',
        'approved_at',
        'picked_by',
        'picked_at',
        'issued_by',
        'issued_at',
        'priority',
        'notes',
        'rejection_reason',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'required_date' => 'date',
            'approved_at' => 'datetime',
            'picked_at' => 'datetime',
            'issued_at' => 'datetime',
            'attachments' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($requisition) {
            if (empty($requisition->reference)) {
                $requisition->reference = self::generateReference();
            }
        });
    }

    private static function generateReference(): string
    {
        $prefix = 'MR';
        $date = now()->format('Ymd');

        $last = self::withTrashed()
            ->where('reference', 'like', "{$prefix}-{$date}-%")
            ->latest('id')
            ->first();

        $number = $last ? intval(substr($last->reference, -4)) + 1 : 1;
        return "{$prefix}-{$date}-" . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function picker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_by');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaterialRequisitionItem::class);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
