<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Deployment\SourceUpdateService;
use Illuminate\Http\JsonResponse;

class SourceUpdateController extends Controller
{
    public function index(?string $project = null): JsonResponse
    {
        $query = Project::query()
            ->with('sourceUpdateState')
            ->orderBy('name');

        if ($project !== null) {
            $query->where('slug', $project);
        }

        return response()->json([
            'list' => $query->get()->map(fn (Project $project): array => [
                'project' => $project->slug,
                'name' => $project->name,
                'status' => $project->sourceUpdateState?->status,
                'rerun' => $project->sourceUpdateState?->rerun ?? false,
                'attempts' => $project->sourceUpdateState?->attempts ?? 0,
                'requested_at' => optional($project->sourceUpdateState?->requested_at)->toIso8601String(),
                'started_at' => optional($project->sourceUpdateState?->started_at)->toIso8601String(),
                'finished_at' => optional($project->sourceUpdateState?->finished_at)->toIso8601String(),
                'last_error' => $project->sourceUpdateState?->last_error,
                'last_log_path' => $project->sourceUpdateState?->last_log_path,
                'worker_id' => $project->sourceUpdateState?->worker_id,
            ]),
        ]);
    }

    public function store(Project $project, SourceUpdateService $sourceUpdates): JsonResponse
    {
        abort_unless($project->active, 404);

        $state = $sourceUpdates->request($project);

        return response()->json([
            'project' => $project->slug,
            'status' => $state->status,
            'rerun' => $state->rerun,
            'requested_at' => optional($state->requested_at)->toIso8601String(),
        ], 202);
    }
}
