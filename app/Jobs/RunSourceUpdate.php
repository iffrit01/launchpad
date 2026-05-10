<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\Deployment\SourceUpdateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunSourceUpdate implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 0;

    public function __construct(public int $projectId)
    {
    }

    public function handle(SourceUpdateService $sourceUpdates): void
    {
        $project = Project::query()
            ->where('active', true)
            ->findOrFail($this->projectId);

        $sourceUpdates->run($project);
    }
}
