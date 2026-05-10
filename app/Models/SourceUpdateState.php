<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceUpdateState extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAIL = 'fail';

    protected $fillable = [
        'project_id',
        'status',
        'rerun',
        'attempts',
        'requested_at',
        'started_at',
        'finished_at',
        'last_error',
        'last_output',
        'last_log_path',
        'worker_id',
    ];

    protected $casts = [
        'rerun' => 'boolean',
        'requested_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
