<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'deployer_file',
        'source_update_task',
        'stage',
        'active',
        'release_branch_patterns',
        'environments',
    ];

    protected $casts = [
        'active' => 'boolean',
        'release_branch_patterns' => 'array',
        'environments' => 'array',
    ];

    public function sourceUpdateState(): HasOne
    {
        return $this->hasOne(SourceUpdateState::class);
    }
}
