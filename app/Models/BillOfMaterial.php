<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillOfMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference',
        'name',
        'product_id',
        'version',
        'parent_bom_id',
        'is_active',
        'is_latest_version',
        'status',
        'description',
        'quantity',
        'unit_id',
        'total_cost',
        'labor_cost',
        'overhead_cost',
        'total_bom_cost',
        'estimated_time_minutes',
        'bom_type',
        'effective_date',
        'expiry_date',
        'created_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'notes',
        'attachments',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'labor_cost' => 'decimal:2',
            'overhead_cost' => 'decimal:2',
            'total_bom_cost' => 'decimal:2',
            'estimated_time_minutes' => 'integer',
            'is_active' => 'boolean',
            'is_latest_version' => 'boolean',
            'effective_date' => 'date',
            'expiry_date' => 'date',
            'approved_at' => 'datetime',
            'attachments' => 'array',
            'custom_fields' => 'array',
        ];
    }

    /**
     * Boot method to auto-generate reference and handle versioning
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bom) {
            if (empty($bom->reference)) {
                $bom->reference = self::generateReference();
            }
        });

        static::updating(function ($bom) {
            // Auto-version when editing an approved BOM
            if ($bom->isDirty() && $bom->getOriginal('status') === 'approved' && !$bom->isDirty('status')) {
                // Create new version instead of updating
                $newBom = $bom->replicate();
                $newBom->parent_bom_id = $bom->id;
                $newBom->version = self::incrementVersion($bom->version);
                $newBom->status = 'draft';
                $newBom->is_latest_version = true;
                $newBom->approved_by = null;
                $newBom->approved_at = null;
                $newBom->reference = self::generateReference();

                // Mark old BOM as not latest
                $bom->is_latest_version = false;
                $bom->saveQuietly();

                // Copy items to new BOM
                foreach ($bom->items as $item) {
                    $newItem = $item->replicate();
                    $newItem->bill_of_material_id = $newBom->id;
                    $newBom->items()->save($newItem);
                }

                return false; // Prevent the update
            }
        });
    }

    /**
     * Generate unique reference
     */
    private static function generateReference(): string
    {
        $prefix = 'BOM';
        $date = now()->format('Ymd');

        $lastBom = self::withTrashed()
            ->where('reference', 'like', "{$prefix}-{$date}-%")
            ->latest('id')
            ->first();

        if ($lastBom) {
            $lastNumber = intval(substr($lastBom->reference, -4));
            $number = $lastNumber + 1;
        } else {
            $number = 1;
        }

        return "{$prefix}-{$date}-" . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Increment version number
     */
    private static function incrementVersion(string $version): string
    {
        $parts = explode('.', $version);
        $parts[count($parts) - 1]++;
        return implode('.', $parts);
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the parent BOM (for versioning)
     */
    public function parentBom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'parent_bom_id');
    }

    /**
     * Get child BOMs (versions)
     */
    public function versions(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class, 'parent_bom_id');
    }

    /**
     * Get creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get approver
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get BOM items
     */
    public function items(): HasMany
    {
        return $this->hasMany(BillOfMaterialItem::class)->orderBy('sequence');
    }

    /**
     * Get top-level items only
     */
    public function topLevelItems(): HasMany
    {
        return $this->hasMany(BillOfMaterialItem::class)->where('level', 1)->orderBy('sequence');
    }

    /**
     * Get production orders using this BOM
     */
    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    /**
     * Calculate total cost from items
     */
    public function calculateTotalCost(): float
    {
        return $this->items()->sum('total_cost');
    }

    /**
     * Calculate and update BOM costs
     */
    public function recalculateCosts(): void
    {
        $this->total_cost = $this->calculateTotalCost();
        $this->total_bom_cost = $this->total_cost + $this->labor_cost + $this->overhead_cost;
        $this->saveQuietly();
    }

    /**
     * Approve BOM
     */
    public function approve(int $userId): bool
    {
        if ($this->status !== 'pending_approval') {
            return false;
        }

        $this->status = 'approved';
        $this->approved_by = $userId;
        $this->approved_at = now();
        return $this->save();
    }

    /**
     * Reject BOM
     */
    public function reject(int $userId, string $reason): bool
    {
        if ($this->status !== 'pending_approval') {
            return false;
        }

        $this->status = 'rejected';
        $this->rejection_reason = $reason;
        $this->approved_by = $userId;
        return $this->save();
    }

    /**
     * Submit for approval
     */
    public function submitForApproval(): bool
    {
        if ($this->status !== 'draft') {
            return false;
        }

        $this->status = 'pending_approval';
        return $this->save();
    }

    /**
     * Check if BOM can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    /**
     * Check if BOM is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if BOM is active and effective
     */
    public function isEffective(): bool
    {
        if (!$this->is_active || $this->status !== 'approved') {
            return false;
        }

        $now = now();

        if ($this->effective_date && $now->lt($this->effective_date)) {
            return false;
        }

        if ($this->expiry_date && $now->gt($this->expiry_date)) {
            return false;
        }

        return true;
    }

    /**
     * Scope for active BOMs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for approved BOMs
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for latest versions only
     */
    public function scopeLatestVersion($query)
    {
        return $query->where('is_latest_version', true);
    }

    /**
     * Scope for effective BOMs
     */
    public function scopeEffective($query)
    {
        return $query->where('is_active', true)
            ->where('status', 'approved')
            ->where(function ($q) {
                $q->whereNull('effective_date')
                    ->orWhere('effective_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now());
            });
    }

    /**
     * Scope by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    /**
     * Scope by product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }
}
