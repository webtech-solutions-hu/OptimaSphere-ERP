<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkCenterPerformanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_center_id',
        'log_date',
        'availability_percentage',
        'performance_percentage',
        'quality_percentage',
        'oee_percentage',
        'total_scheduled_minutes',
        'total_downtime_minutes',
        'total_productive_minutes',
        'total_units_produced',
        'total_units_scrapped',
        'target_units',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'availability_percentage' => 'decimal:2',
            'performance_percentage' => 'decimal:2',
            'quality_percentage' => 'decimal:2',
            'oee_percentage' => 'decimal:2',
            'total_scheduled_minutes' => 'integer',
            'total_downtime_minutes' => 'integer',
            'total_productive_minutes' => 'integer',
            'total_units_produced' => 'decimal:2',
            'total_units_scrapped' => 'decimal:2',
            'target_units' => 'decimal:2',
        ];
    }

    /**
     * Get the work center
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    /**
     * Recalculate performance metrics
     */
    public function recalculateMetrics(): void
    {
        // Availability = (Operating Time / Scheduled Time) × 100
        if ($this->total_scheduled_minutes > 0) {
            $operatingTime = $this->total_scheduled_minutes - $this->total_downtime_minutes;
            $this->availability_percentage = ($operatingTime / $this->total_scheduled_minutes) * 100;
        }

        // Performance = (Actual Units / Target Units) × 100
        if ($this->target_units > 0) {
            $this->performance_percentage = ($this->total_units_produced / $this->target_units) * 100;
        }

        // Quality = (Good Units / Total Units) × 100
        $totalUnits = $this->total_units_produced + $this->total_units_scrapped;
        if ($totalUnits > 0) {
            $this->quality_percentage = ($this->total_units_produced / $totalUnits) * 100;
        }

        // OEE = Availability × Performance × Quality
        $this->oee_percentage = ($this->availability_percentage * $this->performance_percentage * $this->quality_percentage) / 10000;
    }

    /**
     * Get OEE rating
     */
    public function getOeeRatingAttribute(): string
    {
        $oee = $this->oee_percentage;

        if ($oee >= 85) {
            return 'World Class';
        } elseif ($oee >= 60) {
            return 'Good';
        } elseif ($oee >= 40) {
            return 'Fair';
        } else {
            return 'Poor';
        }
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('log_date', [$startDate, $endDate]);
    }

    /**
     * Scope for work center
     */
    public function scopeForWorkCenter($query, int $workCenterId)
    {
        return $query->where('work_center_id', $workCenterId);
    }
}
