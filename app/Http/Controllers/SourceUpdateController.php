<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Deployment\SourceUpdateService;
use App\Support\ProjectViewData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Inertia\Response as InertiaResponse;

class SourceUpdateController extends Controller
{
    public function page(): InertiaResponse
    {
        return inertia('SourceUpdates', [
            'projects' => ProjectViewData::sourceUpdateProjects(),
        ]);
    }

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

    public function log(Project $project): Response
    {
        $state = $project->sourceUpdateState;
        $path = $state?->last_log_path;

        abort_if($path === null || $path === '', 404);

        $logRoot = rtrim(config('launchpad.log_root'), '/');
        abort_unless(Str::startsWith($path, $logRoot.'/'), 403);
        abort_unless(is_file($path), 404);

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
