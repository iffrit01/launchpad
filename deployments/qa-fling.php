<?php

namespace Deployer;

use RuntimeException;

localhost('production');

host('staging.fling.com')
    ->setHostname('ded30036.ded.reflected.net')
    ->setRemoteUser('web1')
    ->set('stage', 'stage')
    ->set('roles', ['web'])
    ->set('deploy_path', '/home/web1/staging.fling.com');

host('www2-ord.fling.com')
    ->setRemoteUser('web1')
    ->set('stage', 'production')
    ->set('roles', ['web'])
    ->set('deploy_path', '/home/web1/qa.fling.com');

host('dev.fling.com')
    ->setHostname('devserver')
    ->setRemoteUser('root')
    ->set('stage', 'dev')
    ->set('roles', ['web'])
    ->set('deploy_path', '/sites/qa.fling.com')
    ->setSshArguments(['-o UserKnownHostsFile=/dev/null', '-o StrictHostKeyChecking=no']);

set('allow_anonymous_stats', false);
set('git_repo', 'git@gitlab-ord.fling.com:gpdev/www-fling-com.git');
set('git_cache', getenv('SOURCE_CACHE') ? rtrim(getenv('SOURCE_CACHE'), '/').'/qa.fling.com' : '/sources/qa.fling.com');
set('lock_folder', '/tmp');
set('lock_file', 'deploy-qa-fling.lock');
set('lock_build_suffix', '');
set('git_tracked_branch_limit', 10);
set('git_clone_timeout', 3600);
set('git_fetch_timeout', 1800);
set('slots', ['slot1', 'slot2']);
set('slots_basepath', '{{deploy_path}}');
set('git_tracked_branches', [
    'refs/remotes/origin/release-2026*',
    'refs/remotes/origin/release-2025*',
    'refs/remotes/origin/master',
    'refs/remotes/origin/feature/*',
    'refs/remotes/origin/bugfix/*',
    'refs/remotes/origin/hotfix/*',
    'refs/remotes/origin/chore/*',
    'refs/remotes/origin/refactor/*',
    'refs/remotes/origin/docs/*',
    'refs/remotes/origin/test/*',
    'refs/remotes/origin/experiment/*',
]);

desc('Update qa-fling source repository cache');
task('deploy:update-cache-qa', function (): void {
    invoke('deploy:lock');
    invoke('deploy:build-lock');

    try {
        invoke('git:update-cache-qa');
    } finally {
        invoke('deploy:build-unlock');
        invoke('deploy:unlock');
    }
});

desc('Print qa-fling host status as JSON');
task('hosts:status-json', function (): void {
    $deployPath = get('deploy_path');
    $current = trim(run('if [ -h '.quote($deployPath.'/current').' ]; then readlink '.quote($deployPath.'/current').'; fi'));
    $path = '';
    $slot = '';
    $rev = '';

    if ($current !== '') {
        $path = str_starts_with($current, '/') ? $current : $deployPath.'/'.$current;
        $slot = basename($path);
        $rev = trim(run('if [ -f '.quote($path.'/GIT_COMMIT').' ]; then cat '.quote($path.'/GIT_COMMIT').'; fi'));
    }

    writeln(json_encode([[
        'host' => currentHost()->getAlias(),
        'hostname' => currentHost()->getHostname(),
        'stage' => get('stage'),
        'path' => $path,
        'slot' => $slot,
        'rev' => $rev,
    ]], JSON_PRETTY_PRINT));
});

desc('Lock qa-fling source update');
task('deploy:lock', function (): void {
    if (test('[ -f {{lock_folder}}/{{lock_file}} ]')) {
        throw new RuntimeException('Deploy locked. Run deploy:unlock to unlock.');
    }

    run('touch {{lock_folder}}/{{lock_file}}');
});

desc('Unlock qa-fling source update');
task('deploy:unlock', function (): void {
    run('rm -f {{lock_folder}}/{{lock_file}}');
});

desc('Lock qa-fling source build on this Launchpad host');
task('deploy:build-lock', function (): void {
    $count = trim(run('ls {{lock_folder}} 2>/dev/null | grep "^{{lock_file}}\." | wc -l'));

    if ((int) $count > 0) {
        throw new RuntimeException('Deploy build locked. Run deploy:build-unlock-all if the lock is stale.');
    }

    $suffix = str_replace(' ', '', microtime());
    set('lock_build_suffix', $suffix);
    run('touch {{lock_folder}}/{{lock_file}}.'.$suffix);
    run('printf %s '.quote($suffix).' > {{lock_folder}}/{{lock_file}}.build_suffix');
});

desc('Unlock qa-fling source build');
task('deploy:build-unlock', function (): void {
    $suffix = get('lock_build_suffix', '');

    if ($suffix === '') {
        $suffix = trim(run('if [ -f {{lock_folder}}/{{lock_file}}.build_suffix ]; then cat {{lock_folder}}/{{lock_file}}.build_suffix; fi'));
    }

    if ($suffix !== '') {
        run('rm -f {{lock_folder}}/{{lock_file}}.'.$suffix);
    }

    run('rm -f {{lock_folder}}/{{lock_file}}.build_suffix');
});

desc('Remove all qa-fling source build locks');
task('deploy:build-unlock-all', function (): void {
    run('rm -f {{lock_folder}}/{{lock_file}}*');
});

desc('Update qa-fling git cache clone');
task('git:update-cache-qa', function (): void {
    $cache = get('git_cache');
    $repo = get('git_repo');

    if (! test('[ -d '.quote($cache).'/.git ]')) {
        run('mkdir -p '.quote(dirname($cache)));
        run('git clone '.quote($repo).' '.quote($cache), timeout: get('git_clone_timeout'));
    }

    run('cd '.quote($cache).' && git checkout -- .');
    run('cd '.quote($cache).' && git clean -fdx');
    run('cd '.quote($cache).' && git fetch --prune origin', timeout: get('git_fetch_timeout'));

    $tracked = implode(' ', array_map(fn (string $ref): string => quote($ref), get('git_tracked_branches')));
    $limit = (int) get('git_tracked_branch_limit', 0);
    $limitArg = $limit > 0 ? '--count='.$limit : '';

    $output = run(
        'cd '.quote($cache).' && git for-each-ref --format="%(committerdate:iso-strict)%09%(refname:short)" --sort=-committerdate '.$limitArg.' '.$tracked
    );

    $branches = [];
    foreach (explode("\n", trim($output)) as $line) {
        if ($line === '') {
            continue;
        }

        [, $ref] = explode("\t", $line, 2);

        if (! str_starts_with($ref, 'origin/')) {
            continue;
        }

        $branch = substr($ref, strlen('origin/'));
        if ($branch === 'HEAD') {
            continue;
        }

        $branches[$branch] = true;
    }

    foreach (array_keys($branches) as $branch) {
        writeln('Processing branch: '.$branch);

        $localExists = test('cd '.quote($cache).' && git show-ref --verify --quiet '.quote('refs/heads/'.$branch));

        if (! $localExists) {
            run('cd '.quote($cache).' && git checkout -B '.quote($branch).' '.quote('origin/'.$branch));
            continue;
        }

        run('cd '.quote($cache).' && git checkout '.quote($branch));
        run('cd '.quote($cache).' && git merge --ff-only '.quote('origin/'.$branch));
    }

    if ($limit > 0 && count($branches) >= $limit) {
        writeln('Reached branch processing limit ('.$limit.'), skipping remaining branches.');
    }
});
