<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $projects = Project::query()
            ->with('sourceUpdateState')
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'slug' => $project->slug,
                'name' => $project->name,
                'active' => $project->active,
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

        return inertia('Dashboard', [
            'projects' => $projects,
        ]);
    }
}
