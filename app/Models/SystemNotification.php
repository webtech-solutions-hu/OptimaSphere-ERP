<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemNotification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'body',
        'type',
        'icon',
        'color',
        'target_type',
        'target_id',
        'target_role_id',
        'scheduled_at',
        'sent_at',
        'status',
        'created_by',
        'actions',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'actions' => 'array',
        ];
    }

    /**
     * Relationships
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targetRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'target_role_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now());
    }

    /**
     * Send notification to target users
     */
    public function send(): void
    {
        $users = $this->getTargetUsers();

        foreach ($users as $user) {
            $notification = \Filament\Notifications\Notification::make()
                ->title($this->title)
                ->body($this->body)
                ->icon($this->icon ?? 'heroicon-o-bell')
                ->color($this->color ?? 'primary');

            // Apply type method if it's a valid method
            if (in_array($this->type, ['info', 'success', 'warning', 'danger'])) {
                $notification = $notification->{$this->type}();
            }

            $notification->sendToDatabase($user);
        }

        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Get target users based on target type
     */
    protected function getTargetUsers()
    {
        return match ($this->target_type) {
            'global' => User::where('is_active', true)->get(),
            'role' => $this->targetRole?->users()->where('is_active', true)->get() ?? collect(),
            'user' => collect([$this->targetUser]),
            default => collect(),
        };
    }
}
