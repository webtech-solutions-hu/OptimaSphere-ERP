<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'type',
        'primary_category_id',
        'company_name',
        'email',
        'phone',
        'mobile',
        'website',
        'contact_person',
        'contact_title',
        'tax_id',
        'registration_number',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'bank_name',
        'bank_account_number',
        'bank_swift_code',
        'bank_iban',
        'payment_terms',
        'payment_method',
        'credit_limit',
        'contract_number',
        'contract_start_date',
        'contract_end_date',
        'contract_terms',
        'category',
        'product_categories',
        'performance_rating',
        'last_transaction_date',
        'total_transactions',
        'total_purchase_amount',
        'assigned_procurement_officer',
        'is_active',
        'is_approved',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'performance_rating' => 'decimal:2',
            'total_purchase_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'is_approved' => 'boolean',
            'payment_terms' => 'integer',
            'total_transactions' => 'integer',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'last_transaction_date' => 'datetime',
            'approved_at' => 'datetime',
            'product_categories' => 'array',
        ];
    }

    /**
     * Boot method to auto-generate supplier code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($supplier) {
            if (empty($supplier->code)) {
                $supplier->code = self::generateSupplierCode();
            }
        });
    }

    /**
     * Generate unique supplier code
     */
    private static function generateSupplierCode(): string
    {
        $prefix = 'SUPP';
        $lastSupplier = self::withTrashed()->latest('id')->first();
        $number = $lastSupplier ? intval(substr($lastSupplier->code, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get the assigned procurement officer
     */
    public function procurementOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_procurement_officer');
    }

    /**
     * Get the user who approved this supplier
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the primary category
     */
    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    /**
     * Get the product categories (many-to-many)
     */
    public function productCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'supplier_category');
    }

    /**
     * Check if supplier is approved
     */
    public function isApproved(): bool
    {
        return $this->is_approved && $this->approved_at !== null;
    }

    /**
     * Check if contract is active
     */
    public function hasActiveContract(): bool
    {
        if (!$this->contract_start_date || !$this->contract_end_date) {
            return false;
        }

        $now = now();
        return $now->between($this->contract_start_date, $this->contract_end_date);
    }

    /**
     * Get performance rating as stars
     */
    public function getPerformanceStarsAttribute(): string
    {
        $rating = $this->performance_rating;
        $fullStars = floor($rating);
        $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
        $emptyStars = 5 - $fullStars - $halfStar;

        return str_repeat('★', $fullStars) . str_repeat('☆', $halfStar) . str_repeat('☆', $emptyStars);
    }

    /**
     * Scope for active suppliers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for approved suppliers
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope by category
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for suppliers with high performance rating
     */
    public function scopeHighPerformance($query, float $minRating = 4.0)
    {
        return $query->where('performance_rating', '>=', $minRating);
    }
}
