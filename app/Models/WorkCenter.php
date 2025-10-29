<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkCenter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'warehouse_id',
        'location_details',
        'capacity_per_day',
        'capacity_unit',
        'efficiency_percentage',
        'utilization_percentage',
        'cost_per_hour',
        'setup_time_minutes',
        'teardown_time_minutes',
        'minimum_batch_size',
        'maximum_batch_size',
        'is_active',
        'is_available',
        'requires_operator',
        'required_operators',
        'operating_hours',
        'capabilities',
        'certifications',
        'maintenance_due_date',
        'maintenance_notes',
        'supervisor_id',
        'notes',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'capacity_per_day' => 'decimal:2',
            'efficiency_percentage' => 'decimal:2',
            'utilization_percentage' => 'decimal:2',
            'cost_per_hour' => 'decimal:2',
            'setup_time_minutes' => 'integer',
            'teardown_time_minutes' => 'integer',
            'minimum_batch_size' => 'integer',
            'maximum_batch_size' => 'integer',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
            'requires_operator' => 'boolean',
            'required_operators' => 'integer',
            'operating_hours' => 'array',
            'capabilities' => 'array',
            'certifications' => 'array',
            'maintenance_due_date' => 'date',
            'custom_fields' => 'array',
        ];
    }

    /**
     * Boot method to auto-generate code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workCenter) {
            if (empty($workCenter->code)) {
                $workCenter->code = self::generateCode();
            }
        });
    }

    /**
     * Generate unique code
     */
    private static function generateCode(): string
    {
        $prefix = 'WC';
        $lastWorkCenter = self::withTrashed()->latest('id')->first();
        $number = $lastWorkCenter ? intval(substr($lastWorkCenter->code, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the warehouse
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the supervisor
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get production schedules
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ProductionSchedule::class);
    }

    /**
     * Get active schedules
     */
    public function activeSchedules(): HasMany
    {
        return $this->hasMany(ProductionSchedule::class)
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Calculate effective capacity
     */
    public function getEffectiveCapacityAttribute(): float
    {
        return $this->capacity_per_day * ($this->efficiency_percentage / 100);
    }

    /**
     * Check if work center has capacity for time range
     */
    public function hasCapacityForTimeRange($startTime, $endTime): bool
    {
        $conflicts = $this->schedules()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('scheduled_start', [$startTime, $endTime])
                    ->orWhereBetween('scheduled_end', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('scheduled_start', '<=', $startTime)
                            ->where('scheduled_end', '>=', $endTime);
                    });
            })
            ->exists();

        return !$conflicts;
    }

    /**
     * Get conflicting schedules for time range
     */
    public function getConflictingSchedules($startTime, $endTime)
    {
        return $this->schedules()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('scheduled_start', [$startTime, $endTime])
                    ->orWhereBetween('scheduled_end', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('scheduled_start', '<=', $startTime)
                            ->where('scheduled_end', '>=', $endTime);
                    });
            })
            ->get();
    }

    /**
     * Calculate utilization for date range
     */
    public function calculateUtilization($startDate, $endDate): float
    {
        $totalMinutes = $this->schedules()
            ->whereBetween('scheduled_start', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled'])
            ->sum('estimated_duration_minutes');

        $days = now()->parse($startDate)->diffInDays($endDate) + 1;
        $availableMinutes = $days * $this->capacity_per_day * 60; // Convert hours to minutes

        if ($availableMinutes <= 0) {
            return 0;
        }

        return ($totalMinutes / $availableMinutes) * 100;
    }

    /**
     * Update utilization percentage
     */
    public function updateUtilization(): void
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        $this->utilization_percentage = $this->calculateUtilization($startDate, $endDate);
        $this->saveQuietly();
    }

    /**
     * Check if maintenance is due
     */
    public function isMaintenanceDue(): bool
    {
        if (!$this->maintenance_due_date) {
            return false;
        }

        return now()->greaterThanOrEqualTo($this->maintenance_due_date);
    }

    /**
     * Check if maintenance is overdue
     */
    public function isMaintenanceOverdue(): bool
    {
        if (!$this->maintenance_due_date) {
            return false;
        }

        return now()->greaterThan($this->maintenance_due_date);
    }

    /**
     * Check if work center can handle batch size
     */
    public function canHandleBatchSize(int $batchSize): bool
    {
        if ($batchSize < $this->minimum_batch_size) {
            return false;
        }

        if ($this->maximum_batch_size && $batchSize > $this->maximum_batch_size) {
            return false;
        }

        return true;
    }

    /**
     * Scope for active work centers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for available work centers
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
            ->where('is_active', true);
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for work centers needing maintenance
     */
    public function scopeMaintenanceDue($query)
    {
        return $query->whereNotNull('maintenance_due_date')
            ->where('maintenance_due_date', '<=', now());
    }

    /**
     * Scope by warehouse
     */
    public function scopeInWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope with capability
     */
    public function scopeWithCapability($query, string $capability)
    {
        return $query->whereJsonContains('capabilities', $capability);
    }
}
