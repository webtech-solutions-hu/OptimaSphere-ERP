<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'returns';

    protected $fillable = [
        'return_number', 'return_type', 'original_document_type', 'original_document_id',
        'customer_id', 'supplier_id', 'warehouse_id', 'status', 'reason', 'return_date',
        'received_at', 'inspected_at', 'approved_at', 'restocked_at', 'requested_by',
        'received_by', 'inspected_by', 'approved_by', 'resolution_action', 'disposition',
        'refund_amount', 'restocking_fee', 'customer_notes', 'internal_notes',
        'inspection_notes', 'rejection_reason', 'attachments',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'received_at' => 'datetime',
            'inspected_at' => 'datetime',
            'approved_at' => 'datetime',
            'restocked_at' => 'datetime',
            'refund_amount' => 'decimal:2',
            'restocking_fee' => 'decimal:2',
            'attachments' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($return) {
            if (empty($return->return_number)) {
                $return->return_number = self::generateReturnNumber();
            }
        });
    }

    private static function generateReturnNumber(): string
    {
        $prefix = 'RET';
        $date = now()->format('Ymd');
        $lastReturn = self::withTrashed()
            ->where('return_number', 'like', $prefix . $date . '%')
            ->latest('id')
            ->first();

        $number = $lastReturn ? intval(substr($lastReturn->return_number, -4)) + 1 : 1;
        return $prefix . $date . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function inspectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    public function restockItems(User $user): bool
    {
        \DB::beginTransaction();

        try {
            foreach ($this->items as $item) {
                if ($item->quantity_approved > 0 && $this->disposition === 'restock') {
                    $stock = ProductWarehouseStock::firstOrCreate(
                        ['product_id' => $item->product_id, 'warehouse_id' => $this->warehouse_id],
                        ['quantity' => 0, 'reserved_quantity' => 0, 'available_quantity' => 0]
                    );

                    $balanceBefore = $stock->quantity;
                    $stock->addStock($item->quantity_approved);

                    StockMovement::create([
                        'warehouse_id' => $this->warehouse_id,
                        'product_id' => $item->product_id,
                        'type' => 'return',
                        'quantity' => $item->quantity_approved,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $stock->quantity,
                        'related_document_type' => 'return',
                        'related_document_id' => $this->id,
                        'created_by' => $user->id,
                        'notes' => "Return {$this->return_number} - {$this->return_type}",
                        'movement_date' => now(),
                    ]);

                    $item->quantity_restocked = $item->quantity_approved;
                    $item->save();
                }
            }

            $this->status = 'restocked';
            $this->restocked_at = now();
            $this->save();

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }
}
