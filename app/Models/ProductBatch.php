<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id', 'warehouse_id', 'batch_number', 'serial_number', 'tracking_type',
        'quantity', 'quantity_available', 'quantity_allocated', 'status',
        'manufacturing_date', 'expiry_date', 'received_date', 'source_document_type',
        'source_document_id', 'storage_location', 'aisle', 'rack', 'shelf', 'bin',
        'supplier_id', 'quality_status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'quantity_available' => 'decimal:2',
            'quantity_allocated' => 'decimal:2',
            'manufacturing_date' => 'date',
            'expiry_date' => 'date',
            'received_date' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BatchSerialTransaction::class);
    }

    /**
     * Check if batch is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }
        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Allocate quantity for order
     */
    public function allocate(float $quantity, User $user, ?string $documentType = null, ?int $documentId = null): bool
    {
        if ($this->quantity_available < $quantity) {
            return false;
        }

        \DB::beginTransaction();

        try {
            $this->quantity_allocated += $quantity;
            $this->quantity_available -= $quantity;

            if ($this->tracking_type === 'serial') {
                $this->status = 'allocated';
            }

            $this->save();

            // Create transaction record
            BatchSerialTransaction::create([
                'product_batch_id' => $this->id,
                'product_id' => $this->product_id,
                'warehouse_id' => $this->warehouse_id,
                'transaction_type' => 'allocation',
                'quantity' => $this->tracking_type === 'batch' ? $quantity : null,
                'quantity_before' => $this->quantity_available + $quantity,
                'quantity_after' => $this->quantity_available,
                'related_document_type' => $documentType,
                'related_document_id' => $documentId,
                'created_by' => $user->id,
                'transaction_date' => now(),
            ]);

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    /**
     * Scopes
     */
    public function scopeAvailable($query)
    {
        return $query->where('quantity_available', '>', 0)
            ->orWhere('status', 'available');
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }
}
