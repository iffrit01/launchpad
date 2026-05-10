<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Deployment\SourceUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GitlabWebhookController extends Controller
{
    public function sourceUpdate(Request $request, Project $project, SourceUpdateService $sourceUpdates): JsonResponse
    {
        $expectedToken = config('launchpad.webhook_token');

        abort_if($expectedToken === null || $expectedToken === '', 500, 'Webhook token is not configured.');
        abort_unless(hash_equals($expectedToken, (string) $request->header('X-Gitlab-Token')), 403);
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
