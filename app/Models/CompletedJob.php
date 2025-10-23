<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompletedJob extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'completed_jobs';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'queue',
        'payload',
        'completed_at',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'integer',
            'created_at' => 'integer',
        ];
    }

    /**
     * Get the job name from payload.
     */
    public function getJobNameAttribute(): ?string
    {
        $payload = json_decode($this->payload, true);
        return $payload['displayName'] ?? $payload['job'] ?? null;
    }

    /**
     * Get the job data from payload.
     */
    public function getJobDataAttribute(): ?array
    {
        $payload = json_decode($this->payload, true);
        return $payload['data'] ?? null;
    }

    /**
     * Get the duration in seconds.
     */
    public function getDurationAttribute(): ?int
    {
        if ($this->completed_at && $this->created_at) {
            return $this->completed_at - $this->created_at;
        }
        return null;
    }
}
