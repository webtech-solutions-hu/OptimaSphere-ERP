<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference',
        'production_order_id',
        'work_center_id',
        'operation_name',
        'sequence',
        'status',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'estimated_duration_minutes',
        'actual_duration_minutes',
        'setup_time_minutes',
        'run_time_minutes',
        'teardown_time_minutes',
        'quantity_scheduled',
        'quantity_completed',
        'quantity_scrapped',
        'assigned_operator_id',
        'started_by',
        'completed_by',
        'priority',
        'has_conflict',
        'conflict_details',
        'operation_notes',
        'completion_notes',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
            'actual_start' => 'datetime',
            'actual_end' => 'datetime',
            'estimated_duration_minutes' => 'integer',
            'actual_duration_minutes' => 'integer',
            'setup_time_minutes' => 'integer',
            'run_time_minutes' => 'integer',
            'teardown_time_minutes' => 'integer',
            'quantity_scheduled' => 'decimal:2',
            'quantity_completed' => 'decimal:2',
            'quantity_scrapped' => 'decimal:2',
            'has_conflict' => 'boolean',
            'custom_fields' => 'array',
        ];
    }

    /**
     * Boot method to auto-generate reference and detect conflicts
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($schedule) {
            if (empty($schedule->reference)) {
                $schedule->reference = self::generateReference();
            }

            // Calculate estimated duration
            if (!$schedule->estimated_duration_minutes) {
                $schedule->estimated_duration_minutes =
                    $schedule->setup_time_minutes +
                    $schedule->run_time_minutes +
                    $schedule->teardown_time_minutes;
            }

            // Set scheduled end if not provided
            if ($schedule->scheduled_start && !$schedule->scheduled_end && $schedule->estimated_duration_minutes) {
                $schedule->scheduled_end = $schedule->scheduled_start->copy()->addMinutes($schedule->estimated_duration_minutes);
            }
        });

        static::created(function ($schedule) {
            $schedule->detectConflicts();
        });

        static::updating(function ($schedule) {
            if ($schedule->isDirty(['scheduled_start', 'scheduled_end', 'work_center_id'])) {
                $schedule->detectConflicts();
            }
        });
    }

    /**
     * Generate unique reference
     */
    private static function generateReference(): string
    {
        $prefix = 'SCH';
        $date = now()->format('Ymd');

        $lastSchedule = self::withTrashed()
            ->where('reference', 'like', "{$prefix}-{$date}-%")
            ->latest('id')
            ->first();

        if ($lastSchedule) {
            $lastNumber = intval(substr($lastSchedule->reference, -4));
            $number = $lastNumber + 1;
        } else {
            $number = 1;
        }

        return "{$prefix}-{$date}-" . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the production order
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /**
     * Get the work center
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    /**
     * Get assigned operator
     */
    public function assignedOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_operator_id');
    }

    /**
     * Get user who started
     */
    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /**
     * Get user who completed
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Detect conflicts with other schedules
     */
    public function detectConflicts(): void
    {
        if (!$this->scheduled_start || !$this->scheduled_end || !$this->work_center_id) {
            return;
        }

        $conflicts = ProductionSchedule::where('work_center_id', $this->work_center_id)
            ->where('id', '!=', $this->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where(function ($query) {
                $query->whereBetween('scheduled_start', [$this->scheduled_start, $this->scheduled_end])
                    ->orWhereBetween('scheduled_end', [$this->scheduled_start, $this->scheduled_end])
                    ->orWhere(function ($q) {
                        $q->where('scheduled_start', '<=', $this->scheduled_start)
                            ->where('scheduled_end', '>=', $this->scheduled_end);
                    });
            })
            ->get();

        if ($conflicts->count() > 0) {
            $this->has_conflict = true;
            $conflictDetails = $conflicts->map(function ($conflict) {
                return "Conflicts with schedule {$conflict->reference} " .
                       "({$conflict->scheduled_start->format('Y-m-d H:i')} - " .
                       "{$conflict->scheduled_end->format('Y-m-d H:i')})";
            })->implode('; ');
            $this->conflict_details = $conflictDetails;

            // Mark conflicting schedules
            foreach ($conflicts as $conflict) {
                $conflict->has_conflict = true;
                $conflict->conflict_details = "Conflicts with schedule {$this->reference}";
                $conflict->saveQuietly();
            }
        } else {
            $this->has_conflict = false;
            $this->conflict_details = null;
        }

        $this->saveQuietly();
    }

    /**
     * Start schedule
     */
    public function start(int $userId): bool
    {
        if ($this->status !== 'ready' && $this->status !== 'scheduled') {
            return false;
        }

        $this->status = 'in_progress';
        $this->actual_start = now();
        $this->started_by = $userId;

        return $this->save();
    }

    /**
     * Complete schedule
     */
    public function complete(int $userId, float $quantityCompleted, float $quantityScrapped = 0): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }

        $this->status = 'completed';
        $this->actual_end = now();
        $this->completed_by = $userId;
        $this->quantity_completed = $quantityCompleted;
        $this->quantity_scrapped = $quantityScrapped;

        // Calculate actual duration
        if ($this->actual_start) {
            $this->actual_duration_minutes = $this->actual_start->diffInMinutes($this->actual_end);
        }

        // Update production order quantities
        if ($this->productionOrder) {
            $this->productionOrder->quantity_produced += $quantityCompleted;
            $this->productionOrder->quantity_scrapped += $quantityScrapped;
            $this->productionOrder->save();
        }

        return $this->save();
    }

    /**
     * Put schedule on hold
     */
    public function putOnHold(): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }

        $this->status = 'on_hold';
        return $this->save();
    }

    /**
     * Resume schedule
     */
    public function resume(): bool
    {
        if ($this->status !== 'on_hold') {
            return false;
        }

        $this->status = 'in_progress';
        return $this->save();
    }

    /**
     * Cancel schedule
     */
    public function cancel(): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        $this->status = 'cancelled';
        return $this->save();
    }

    /**
     * Reschedule
     */
    public function reschedule($newStart, $newEnd): bool
    {
        if ($this->status === 'completed') {
            return false;
        }

        $this->scheduled_start = $newStart;
        $this->scheduled_end = $newEnd;

        return $this->save();
    }

    /**
     * Check if schedule is overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->scheduled_end || in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        return now()->greaterThan($this->scheduled_end);
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        if ($this->quantity_scheduled <= 0) {
            return 0;
        }

        return ($this->quantity_completed / $this->quantity_scheduled) * 100;
    }

    /**
     * Get duration variance (actual vs estimated)
     */
    public function getDurationVarianceAttribute(): ?int
    {
        if (!$this->actual_duration_minutes || !$this->estimated_duration_minutes) {
            return null;
        }

        return $this->actual_duration_minutes - $this->estimated_duration_minutes;
    }

    /**
     * Scope for active schedules
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope by priority
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for schedules with conflicts
     */
    public function scopeWithConflicts($query)
    {
        return $query->where('has_conflict', true);
    }

    /**
     * Scope for overdue schedules
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled'])
            ->where('scheduled_end', '<', now());
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('scheduled_start', [$startDate, $endDate]);
    }

    /**
     * Scope for work center
     */
    public function scopeForWorkCenter($query, int $workCenterId)
    {
        return $query->where('work_center_id', $workCenterId);
    }

    /**
     * Scope for assigned operator
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_operator_id', $userId);
    }
}
