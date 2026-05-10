<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Support\Collection;

class ProjectViewData
{
    public static function sourceUpdateProjects(): Collection
    {
        return Project::query()
            ->with('sourceUpdateState')
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'slug' => $project->slug,
                'name' => $project->name,
                'active' => $project->active,
                'source_update_url' => url("/projects/{$project->slug}/source-update"),
                'source_update_log_url' => url("/projects/{$project->slug}/source-update/log"),
                'source_update' => $project->sourceUpdateState ? [
                    'status' => $project->sourceUpdateState->status,
                    'rerun' => $project->sourceUpdateState->rerun,
                    'attempts' => $project->sourceUpdateState->attempts,
                    'requested_at' => optional($project->sourceUpdateState->requested_at)->toIso8601String(),
                    'started_at' => optional($project->sourceUpdateState->started_at)->toIso8601String(),
                    'finished_at' => optional($project->sourceUpdateState->finished_at)->toIso8601String(),
                    'last_error' => $project->sourceUpdateState->last_error,
                    'last_log_path' => $project->sourceUpdateState->last_log_path,
                    'worker_id' => $project->sourceUpdateState->worker_id,
                ] : null,
            ]);
    }
}
