<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'currency',
        'is_active',
        'valid_from',
        'valid_to',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    /**
     * Get customers using this price list
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Scope for active price lists
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if price list is currently valid
     */
    public function isValid(): bool
    {
        $now = now()->startOfDay();

        if ($this->valid_from && $now->isBefore($this->valid_from)) {
            return false;
        }

        if ($this->valid_to && $now->isAfter($this->valid_to)) {
            return false;
        }

        return $this->is_active;
    }
}
