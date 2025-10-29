<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkCenterMaintenance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference',
        'work_center_id',
        'maintenance_type',
        'status',
        'scheduled_date',
        'started_at',
        'completed_at',
        'estimated_duration_minutes',
        'actual_duration_minutes',
        'estimated_cost',
        'actual_cost',
        'description',
        'work_performed',
        'parts_used',
        'assigned_to',
        'performed_by',
        'approved_by',
        'approved_at',
        'priority',
        'affects_production',
        'next_maintenance_date',
        'notes',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'approved_at' => 'datetime',
            'estimated_duration_minutes' => 'integer',
            'actual_duration_minutes' => 'integer',
            'estimated_cost' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'affects_production' => 'boolean',
            'next_maintenance_date' => 'date',
            'attachments' => 'array',
        ];
    }

    /**
     * Boot method to auto-generate reference
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($maintenance) {
            if (empty($maintenance->reference)) {
                $maintenance->reference = self::generateReference();
            }
        });
    }

    /**
     * Generate unique reference
     */
    private static function generateReference(): string
    {
        $prefix = 'MNT';
        $date = now()->format('Ymd');

        $lastMaintenance = self::withTrashed()
            ->where('reference', 'like', "{$prefix}-{$date}-%")
            ->latest('id')
            ->first();

        if ($lastMaintenance) {
            $lastNumber = intval(substr($lastMaintenance->reference, -4));
            $number = $lastNumber + 1;
        } else {
            $number = 1;
        }

        return "{$prefix}-{$date}-" . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the work center
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    /**
     * Get assigned user
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get performer
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get approver
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Start maintenance
     */
    public function start(int $userId): bool
    {
        if ($this->status !== 'scheduled') {
            return false;
        }

        $this->status = 'in_progress';
        $this->started_at = now();
        $this->performed_by = $userId;

        // Mark work center as unavailable if affects production
        if ($this->affects_production) {
            $this->workCenter->is_available = false;
            $this->workCenter->save();
        }

        return $this->save();
    }

    /**
     * Complete maintenance
     */
    public function complete(int $userId): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }

        $this->status = 'completed';
        $this->completed_at = now();

        // Calculate actual duration
        if ($this->started_at) {
            $this->actual_duration_minutes = $this->started_at->diffInMinutes($this->completed_at);
        }

        // Update work center maintenance due date
        if ($this->next_maintenance_date) {
            $this->workCenter->maintenance_due_date = $this->next_maintenance_date;
        }

        // Mark work center as available
        $this->workCenter->is_available = true;
        $this->workCenter->save();

        return $this->save();
    }

    /**
     * Cancel maintenance
     */
    public function cancel(): bool
    {
        if ($this->status === 'completed') {
            return false;
        }

        $this->status = 'cancelled';

        // Mark work center as available if it was unavailable
        if ($this->affects_production && !$this->workCenter->is_available) {
            $this->workCenter->is_available = true;
            $this->workCenter->save();
        }

        return $this->save();
    }

    /**
     * Check if maintenance is overdue
     */
    public function isOverdue(): bool
    {
        if ($this->status === 'completed' || $this->status === 'cancelled') {
            return false;
        }

        return now()->greaterThan($this->scheduled_date);
    }

    /**
     * Scope for active maintenance
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'in_progress']);
    }

    /**
     * Scope by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope by maintenance type
     */
    public function scopeMaintenanceType($query, string $type)
    {
        return $query->where('maintenance_type', $type);
    }

    /**
     * Scope for overdue maintenance
     */
    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['scheduled'])
            ->where('scheduled_date', '<', now());
    }

    /**
     * Scope for upcoming maintenance
     */
    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->where('status', 'scheduled')
            ->whereBetween('scheduled_date', [now(), now()->addDays($days)]);
    }
}
