<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedJob extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'failed_jobs';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'failed_at' => 'datetime',
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
     * Get the exception message.
     */
    public function getExceptionMessageAttribute(): ?string
    {
        preg_match('/^([^\n]+)/', $this->exception, $matches);
        return $matches[1] ?? 'Unknown exception';
    }
}
