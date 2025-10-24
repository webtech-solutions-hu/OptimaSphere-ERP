<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'type',
        'company_name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'website',
        'tax_id',
        'registration_number',
        'billing_address',
        'shipping_address',
        'city',
        'state',
        'postal_code',
        'country',
        'payment_terms',
        'credit_limit',
        'payment_method',
        'region',
        'category',
        'account_group',
        'assigned_sales_rep',
        'price_list_id',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'is_active' => 'boolean',
            'payment_terms' => 'integer',
        ];
    }

    /**
     * Boot method to auto-generate customer code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->code)) {
                $customer->code = self::generateCustomerCode();
            }
        });
    }

    /**
     * Generate unique customer code
     */
    private static function generateCustomerCode(): string
    {
        $prefix = 'CUST';
        $lastCustomer = self::withTrashed()->latest('id')->first();
        $number = $lastCustomer ? intval(substr($lastCustomer->code, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get the full name of the customer
     */
    public function getFullNameAttribute(): string
    {
        if ($this->type === 'b2b' && $this->company_name) {
            return $this->company_name;
        }

        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')) ?: $this->email;
    }

    /**
     * Get the display name (company or individual)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name;
    }

    /**
     * Get the assigned sales representative
     */
    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_sales_rep');
    }

    /**
     * Get the price list
     */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    /**
     * Scope for active customers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for B2B customers
     */
    public function scopeB2b($query)
    {
        return $query->where('type', 'b2b');
    }

    /**
     * Scope for B2C customers
     */
    public function scopeB2c($query)
    {
        return $query->where('type', 'b2c');
    }
}
