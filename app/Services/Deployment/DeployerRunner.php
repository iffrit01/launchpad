<?php

namespace App\Services\Deployment;

use App\Models\Project;
use RuntimeException;
use Symfony\Component\Process\Process;

class DeployerRunner
{
    public function runSourceUpdate(Project $project, string $logPath): string
    {
        if ($project->source_update_task === null) {
            throw new RuntimeException("Project {$project->slug} has no source update task configured.");
        }

        $deployerFile = $this->deployerFile($project);

        $command = [
            base_path('vendor/bin/dep'),
            '--file='.$deployerFile,
            $project->source_update_task,
            '-vvv',
            '--',
            $project->stage,
        ];

        $process = new Process(
            command: $command,
            cwd: base_path(),
            env: [
                'DEPLOYER_USERNAME' => config('launchpad.deployer_username'),
                'SOURCE_CACHE' => config('launchpad.source_root'),
            ],
            timeout: null,
        );

        $output = '';
        $process->run(function (string $type, string $buffer) use (&$output, $logPath): void {
            $output .= $buffer;
            file_put_contents($logPath, $buffer, FILE_APPEND);
        });

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($output) ?: 'Deployer source update failed.');
        }

        return $output;
    }

    private function deployerFile(Project $project): string
    {
        if (str_starts_with($project->deployer_file, '/')) {
            return $project->deployer_file;
        }

        return base_path($project->deployer_file);
    }
}
