<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Deployment\ReleaseInspector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Response;

class ReleaseController extends Controller
{
    public function index(ReleaseInspector $inspector): Response
    {
        $projects = Project::query()
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project): array => $this->projectPayload($project, $inspector));

        return inertia('Releases', [
            'projects' => $projects,
        ]);
    }

    public function commits(Project $project, Request $request, ReleaseInspector $inspector): JsonResponse
    {
        $left = (string) $request->query('uphead', 'master');
        $right = (string) $request->query('downhead', '');
        $side = (string) $request->query('side', 'left');

        if ($right === '') {
            return response()->json(['message' => 'Missing lower release limit.'], 422);
        }

        return response()->json([
            'list' => $inspector->commits($project, $left, $right, $side),
        ]);
    }

    private function projectPayload(Project $project, ReleaseInspector $inspector): array
    {
        $branches = $this->masterFirst($inspector->branches($project));
        $branches = $this->attachCommitWindows($project, $branches, $inspector);

        return [
            'id' => $project->id,
            'slug' => $project->slug,
            'name' => $project->name,
            'branches' => $branches,
            'environments' => $inspector->environmentPointers($project),
        ];
    }

    private function masterFirst(Collection $branches): Collection
    {
        $master = $branches->firstWhere('ref', 'master');

        if (! $master) {
            return $branches->values();
        }

        return collect([$master])
            ->merge($branches->reject(fn (array $branch): bool => $branch['ref'] === 'master'))
            ->values();
    }

    private function attachCommitWindows(Project $project, Collection $branches, ReleaseInspector $inspector): Collection
    {
        return $branches->map(function (array $branch, int $index) use ($branches, $project, $inspector): array {
            $right = $branches->get($index + 1, $branches->first());
            $commits = collect([[
                'sha' => $branch['sha'],
                'date' => $branch['date'],
                'author' => $branch['author'],
                'message' => $branch['message'],
                'marker' => '',
            ]]);

            if ($right && $right['sha'] !== $branch['sha']) {
                $window = $inspector->commits($project, $branch['sha'], $right['sha']);

                if ($window->isNotEmpty()) {
                    $commits = $window;
                } else {
                    $branch['overlap'] = true;
                }
            }

            return [
                ...$branch,
                'compare_to' => $right ? [
                    'ref' => $right['ref'],
                    'sha' => $right['sha'],
                ] : null,
                'commits' => $commits,
            ];
        })->values();
    }
}
