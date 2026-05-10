<?php

namespace App\Services\Deployment;

use App\Jobs\RunSourceUpdate;
use App\Models\Project;
use App\Models\SourceUpdateState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SourceUpdateService
{
    public function __construct(private readonly DeployerRunner $runner)
    {
    }

    public function request(Project $project): SourceUpdateState
    {
        [$state, $shouldDispatch] = DB::transaction(function () use ($project): array {
            $state = SourceUpdateState::query()
                ->where('project_id', $project->id)
                ->lockForUpdate()
                ->first();

            $state ??= new SourceUpdateState(['project_id' => $project->id]);

            $isRunning = $state->status === SourceUpdateState::STATUS_RUNNING;

            if ($state->status === SourceUpdateState::STATUS_RUNNING) {
                $state->rerun = true;
            } else {
                $state->status = SourceUpdateState::STATUS_PENDING;
                $state->rerun = false;
            }

            $state->requested_at = now();
            $state->save();

            return [$state->fresh(), ! $isRunning];
        });

        if ($shouldDispatch) {
            RunSourceUpdate::dispatch($project->id);
        }

        return $state;
    }

    public function run(Project $project): SourceUpdateState
    {
        $state = $this->claim($project);

        if ($state === null) {
            return $project->sourceUpdateState()->firstOrFail();
        }

        $logPath = null;

        try {
            $logPath = $this->logPath($project);
            $output = $this->runner->runSourceUpdate($project, $logPath);

            return $this->complete($project, [
                'status' => SourceUpdateState::STATUS_SUCCESS,
                'last_error' => null,
                'last_output' => $this->trimOutput($output),
                'last_log_path' => $logPath,
            ]);
        } catch (Throwable $e) {
            return $this->complete($project, [
                'status' => SourceUpdateState::STATUS_FAIL,
                'last_error' => $e->getMessage(),
                'last_log_path' => $logPath,
            ]);
        }
    }

    private function claim(Project $project): ?SourceUpdateState
    {
        return DB::transaction(function () use ($project): ?SourceUpdateState {
            $state = SourceUpdateState::query()
                ->where('project_id', $project->id)
                ->lockForUpdate()
                ->first();

            if ($state === null || $state->status !== SourceUpdateState::STATUS_PENDING) {
                return null;
            }

            $state->fill([
                'status' => SourceUpdateState::STATUS_RUNNING,
                'rerun' => false,
                'attempts' => $state->attempts + 1,
                'started_at' => now(),
                'finished_at' => null,
                'worker_id' => gethostname().':'.getmypid().':'.Str::random(8),
            ]);
            $state->save();

            return $state->fresh();
        });
    }

    private function complete(Project $project, array $attributes): SourceUpdateState
    {
        [$state, $rerun] = DB::transaction(function () use ($project, $attributes): array {
            $state = SourceUpdateState::query()
                ->where('project_id', $project->id)
                ->lockForUpdate()
                ->firstOrFail();

            $rerun = $state->rerun;

            $state->fill($attributes);
            $state->finished_at = now();

            if ($rerun) {
                $state->status = SourceUpdateState::STATUS_PENDING;
                $state->rerun = false;
            }

            $state->save();

            return [$state->fresh(), $rerun];
        });

        if ($rerun) {
            RunSourceUpdate::dispatch($project->id);
        }

        return $state;
    }

    private function logPath(Project $project): string
    {
        $directory = rtrim(config('launchpad.log_root'), '/').'/source-updates/'.$project->slug;

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        return $directory.'/'.now()->format('Ymd_His').'_'.Str::random(8).'.log';
    }

    private function trimOutput(string $output): string
    {
        return Str::limit($output, 60000, '... [truncated]');
    }
}
