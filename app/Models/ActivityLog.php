<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'event',
        'description',
        'properties',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the activity log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log an activity.
     */
    public static function log(
        string $event,
        string $description,
        ?User $user = null,
        ?array $properties = null
    ): self {
        $userId = $user?->id ?? auth()->id();

        // Check if the same event was logged in the last 2 seconds to prevent duplicates
        $recentLog = static::where('user_id', $userId)
            ->where('event', $event)
            ->where('description', $description)
            ->where('created_at', '>', now()->subSeconds(2))
            ->first();

        if ($recentLog) {
            return $recentLog;
        }

        return static::create([
            'user_id' => $userId,
            'event' => $event,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
