<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionOrderOperation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'production_order_id',
        'production_schedule_id',
        'work_center_id',
        'operation_name',
        'sequence',
        'status',
        'started_at',
        'completed_at',
        'paused_at',
        'total_pause_minutes',
        'estimated_duration_minutes',
        'actual_duration_minutes',
        'quantity_to_process',
        'quantity_completed',
        'quantity_scrapped',
        'operator_id',
        'started_by',
        'completed_by',
        'barcode',
        'qr_code',
        'operation_notes',
        'quality_notes',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'paused_at' => 'datetime',
            'total_pause_minutes' => 'integer',
            'estimated_duration_minutes' => 'integer',
            'actual_duration_minutes' => 'integer',
            'quantity_to_process' => 'decimal:2',
            'quantity_completed' => 'decimal:2',
            'quantity_scrapped' => 'decimal:2',
            'custom_fields' => 'array',
        ];
    }

    /**
     * Boot method to generate barcode/QR code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($operation) {
            if (empty($operation->barcode)) {
                $operation->barcode = self::generateBarcode();
            }
            if (empty($operation->qr_code)) {
                $operation->qr_code = self::generateQRCode();
            }
        });
    }

    /**
     * Generate unique barcode
     */
    private static function generateBarcode(): string
    {
        return 'OP' . str_pad(mt_rand(1, 999999999), 9, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique QR code string
     */
    private static function generateQRCode(): string
    {
        return 'QR-OP-' . uniqid() . '-' . mt_rand(1000, 9999);
    }

    /**
     * Get the production order
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /**
     * Get the production schedule
     */
    public function productionSchedule(): BelongsTo
    {
        return $this->belongsTo(ProductionSchedule::class);
    }

    /**
     * Get the work center
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    /**
     * Get the operator
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
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
     * Get time logs
     */
    public function timeLogs(): HasMany
    {
        return $this->hasMany(OperationTimeLog::class);
    }

    /**
     * Start operation
     */
    public function start(int $userId, ?string $scanMethod = 'manual', ?string $scannedCode = null): bool
    {
        if ($this->status !== 'ready' && $this->status !== 'pending') {
            return false;
        }

        $this->status = 'in_progress';
        $this->started_at = now();
        $this->started_by = $userId;
        $this->save();

        // Log the start
        OperationTimeLog::create([
            'production_order_operation_id' => $this->id,
            'operator_id' => $userId,
            'log_type' => 'start',
            'logged_at' => now(),
            'scan_method' => $scanMethod,
            'scanned_code' => $scannedCode,
        ]);

        return true;
    }

    /**
     * Pause operation
     */
    public function pause(int $userId): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }

        $this->status = 'paused';
        $this->paused_at = now();
        $this->save();

        // Log the pause
        OperationTimeLog::create([
            'production_order_operation_id' => $this->id,
            'operator_id' => $userId,
            'log_type' => 'pause',
            'logged_at' => now(),
        ]);

        return true;
    }

    /**
     * Resume operation
     */
    public function resume(int $userId): bool
    {
        if ($this->status !== 'paused') {
            return false;
        }

        // Calculate pause duration
        if ($this->paused_at) {
            $pauseMinutes = $this->paused_at->diffInMinutes(now());
            $this->total_pause_minutes += $pauseMinutes;
        }

        $this->status = 'in_progress';
        $this->paused_at = null;
        $this->save();

        // Log the resume
        OperationTimeLog::create([
            'production_order_operation_id' => $this->id,
            'operator_id' => $userId,
            'log_type' => 'resume',
            'logged_at' => now(),
        ]);

        return true;
    }

    /**
     * Complete operation
     */
    public function complete(int $userId, float $quantityCompleted, float $quantityScrapped = 0, ?string $scanMethod = 'manual', ?string $scannedCode = null): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }

        $this->status = 'completed';
        $this->completed_at = now();
        $this->completed_by = $userId;
        $this->quantity_completed = $quantityCompleted;
        $this->quantity_scrapped = $quantityScrapped;

        // Calculate actual duration (excluding pauses)
        if ($this->started_at) {
            $totalMinutes = $this->started_at->diffInMinutes($this->completed_at);
            $this->actual_duration_minutes = $totalMinutes - $this->total_pause_minutes;
        }

        $this->save();

        // Log the completion
        OperationTimeLog::create([
            'production_order_operation_id' => $this->id,
            'operator_id' => $userId,
            'log_type' => 'complete',
            'logged_at' => now(),
            'quantity_processed' => $quantityCompleted,
            'quantity_scrapped' => $quantityScrapped,
            'scan_method' => $scanMethod,
            'scanned_code' => $scannedCode,
        ]);

        // Update work center performance
        $this->updateWorkCenterPerformance();

        return true;
    }

    /**
     * Record scrap
     */
    public function recordScrap(int $userId, float $quantity, ?string $notes = null): bool
    {
        $this->quantity_scrapped += $quantity;
        $this->save();

        // Log the scrap
        OperationTimeLog::create([
            'production_order_operation_id' => $this->id,
            'operator_id' => $userId,
            'log_type' => 'scrap',
            'logged_at' => now(),
            'quantity_scrapped' => $quantity,
            'notes' => $notes,
        ]);

        return true;
    }

    /**
     * Update work center performance metrics
     */
    protected function updateWorkCenterPerformance(): void
    {
        if (!$this->work_center_id) {
            return;
        }

        $today = now()->toDateString();
        $performanceLog = WorkCenterPerformanceLog::firstOrCreate(
            [
                'work_center_id' => $this->work_center_id,
                'log_date' => $today,
            ],
            [
                'availability_percentage' => 100,
                'performance_percentage' => 100,
                'quality_percentage' => 100,
                'oee_percentage' => 100,
            ]
        );

        // Add to totals
        $performanceLog->total_units_produced += $this->quantity_completed;
        $performanceLog->total_units_scrapped += $this->quantity_scrapped;
        $performanceLog->total_productive_minutes += $this->actual_duration_minutes ?? 0;

        // Recalculate metrics
        $performanceLog->recalculateMetrics();
        $performanceLog->save();
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        if ($this->quantity_to_process <= 0) {
            return 0;
        }

        return ($this->quantity_completed / $this->quantity_to_process) * 100;
    }

    /**
     * Get duration variance
     */
    public function getDurationVarianceAttribute(): ?int
    {
        if (!$this->actual_duration_minutes || !$this->estimated_duration_minutes) {
            return null;
        }

        return $this->actual_duration_minutes - $this->estimated_duration_minutes;
    }

    /**
     * Check if operation is overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->estimated_duration_minutes || !$this->started_at || $this->status === 'completed') {
            return false;
        }

        $expectedCompletion = $this->started_at->copy()->addMinutes($this->estimated_duration_minutes);
        return now()->greaterThan($expectedCompletion);
    }

    /**
     * Scope for active operations
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['ready', 'in_progress', 'paused']);
    }

    /**
     * Scope by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for operations assigned to operator
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('operator_id', $userId);
    }

    /**
     * Scope by work center
     */
    public function scopeForWorkCenter($query, int $workCenterId)
    {
        return $query->where('work_center_id', $workCenterId);
    }
}
