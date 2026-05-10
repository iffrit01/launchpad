<?php

namespace App\Services\Deployment;

use App\Models\Project;
use Illuminate\Support\Collection;
use Symfony\Component\Process\Process;

class ReleaseInspector
{
    private array $sides = [
        'left' => 'left-only',
        'right' => 'right-only',
        'both' => 'left-right',
    ];

    public function branches(Project $project, int $limit = 30): Collection
    {
        $cache = $this->sourceCachePath($project);

        if (! is_dir($cache.'/.git')) {
            return collect();
        }

        $patterns = collect($project->release_branch_patterns ?: ['master'])
            ->map(fn (string $pattern): string => 'refs/remotes/origin/'.$pattern)
            ->all();

        $process = new Process([
            'git',
            'for-each-ref',
            '--format=%(objectname:short)%09%(committerdate:iso-strict)%09%(authorname)%09%(contents:subject)%09%(refname:short)',
            '--sort=-committerdate',
            '--count='.$limit,
            ...$patterns,
        ], $cache);

        $process->run();

        if (! $process->isSuccessful()) {
            return collect();
        }

        return collect(explode("\n", trim($process->getOutput())))
            ->filter()
            ->map(function (string $line): array {
                [$sha, $date, $author, $message, $ref] = array_pad(explode("\t", $line, 5), 5, '');

                return [
                    'sha' => $sha,
                    'date' => $date,
                    'author' => $author,
                    'message' => $message,
                    'ref' => str_starts_with($ref, 'origin/') ? substr($ref, 7) : $ref,
                ];
            })
            ->values();
    }

    public function commits(Project $project, string $left, string $right, string $side = 'left'): Collection
    {
        $cache = $this->sourceCachePath($project);

        if (! is_dir($cache.'/.git') || ! array_key_exists($side, $this->sides)) {
            return collect();
        }

        $process = Process::fromShellCommandline(
            sprintf(
                "git log --cherry-mark --%s --pretty='format:%%h\t%%ci\t%%an\t%%s\t%%m\t%%b----launchpad-commit----' %s...%s",
                $this->sides[$side],
                escapeshellarg($left),
                escapeshellarg($right),
            ),
            $cache,
        );

        $process->run();

        if (! $process->isSuccessful()) {
            return collect();
        }

        return collect(explode('----launchpad-commit----', $process->getOutput()))
            ->map(fn (string $entry): string => trim($entry))
            ->filter()
            ->map(function (string $entry): array {
                [$sha, $date, $author, $message, $marker, $body] = array_pad(explode("\t", $entry, 6), 6, '');

                $commit = [
                    'sha' => $sha,
                    'date' => $date,
                    'author' => $author,
                    'message' => $message,
                    'marker' => $marker,
                ];

                if (preg_match('/\(cherry picked from commit ([a-f0-9]+)\)/i', $body, $matches)) {
                    $commit['cherry'] = $matches[1];
                }

                return $commit;
            })
            ->values();
    }

    public function environmentPointers(Project $project): Collection
    {
        return collect($project->environments ?: [])
            ->map(fn (array $environment): array => $this->environmentPointer($project, $environment))
            ->values();
    }

    private function environmentPointer(Project $project, array $environment): array
    {
        $process = new Process([
            base_path('vendor/bin/dep'),
            '--file='.base_path($project->deployer_file),
            'hosts:status-json',
            '--',
            $environment['selector'],
        ], base_path(), [
            'SOURCE_CACHE' => config('launchpad.source_root'),
        ], timeout: 30);

        $process->run();

        $base = [
            'name' => $environment['name'],
            'label' => $environment['label'],
            'host' => null,
            'deploy_path' => null,
            'status' => 'unknown',
            'slot' => null,
            'path' => null,
            'sha' => null,
            'summary' => null,
            'error' => null,
        ];

        if (! $process->isSuccessful()) {
            return [
                ...$base,
                'error' => trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'Unable to read environment pointer.',
            ];
        }

        $rows = $this->decodeJsonRows($process->getOutput());
        $data = is_array($rows) ? ($rows[0] ?? []) : [];

        $rev = trim($data['rev'] ?? '');
        [$sha] = explode(' ', $rev, 2);

        return [
            ...$base,
            'status' => $sha !== '' ? 'ok' : 'empty',
            'host' => $data['host'] ?? null,
            'deploy_path' => isset($data['path']) ? dirname($data['path']) : null,
            'slot' => ($data['slot'] ?? '') ?: null,
            'path' => ($data['path'] ?? '') ?: null,
            'sha' => $sha ?: null,
            'summary' => $sha !== '' ? $this->commitSummary($project, $sha) : null,
        ];
    }

    private function decodeJsonRows(string $output): array
    {
        if (preg_match('/\[\s*\{.*\}\s*\]/s', $output, $matches)) {
            return json_decode($matches[0], true) ?: [];
        }

        return json_decode(trim($output), true) ?: [];
    }

    private function commitSummary(Project $project, string $sha): ?string
    {
        $cache = $this->sourceCachePath($project);

        if (! is_dir($cache.'/.git')) {
            return $sha;
        }

        $process = new Process(['git', 'show', '--oneline', '--quiet', $sha], $cache);
        $process->run();

        return $process->isSuccessful() ? trim($process->getOutput()) : $sha;
    }

    private function sourceCachePath(Project $project): string
    {
        return rtrim(config('launchpad.source_root'), '/').'/'.$this->sourceDirectoryName($project);
    }

    private function sourceDirectoryName(Project $project): string
    {
        return match ($project->slug) {
            'qa-fling' => 'qa.fling.com',
            default => $project->slug,
        };
    }
}
