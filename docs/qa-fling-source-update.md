# QA Fling Source Update

This is the first project flow ported from DeployUI to Launchpad.

## Goal

Match the existing DeployUI source-cache update behavior for `qa-fling`, using Deployer 8 and Launchpad queue state.

## Current DeployUI Behavior

DeployUI runs:

```bash
/app/vendor/bin/dep --file="/app/vendor/gpdev/flingdep/qa-fling.php" deploy:update-cache-qa -vvv -- production
```

That updates the Git cache at:

```text
/usr/sites/deployment/source/qa.fling.com
```

The source repository is:

```text
git@gitlab-ord.fling.com:gpdev/www-fling-com.git
```

## Launchpad Files

- Project config: `config/projects.php`
- Deployer recipe: `deployments/qa-fling.php`
- Queue job: `app/Jobs/RunSourceUpdate.php`
- State service: `app/Services/Deployment/SourceUpdateService.php`
- Deployer wrapper: `app/Services/Deployment/DeployerRunner.php`

## State Model

Launchpad keeps one `source_update_states` row per project.

When a webhook arrives:

- If no job is running, status becomes `pending` and a queue job is dispatched.
- If a job is already `running`, `rerun` becomes true.
- When the running job finishes, Launchpad marks the project `pending` again if `rerun` was true.
- This prevents duplicate parallel source updates for the same project.

## Local Test Flow

Start Launchpad:

```bash
docker compose up -d
```

Run migrations and seed project config:

```bash
docker compose run --rm app php artisan migrate --seed
```

Request a source update manually:

```bash
curl -X POST http://localhost:8080/projects/qa-fling/source-update
```

Check status:

```bash
curl http://localhost:8080/source-updates/qa-fling
```

Watch worker logs:

```bash
docker compose logs -f worker
```

## Server Requirements Before Real End-to-End Test

The PHP container must have:

- SSH key access to GitLab.
- `/sources` mounted to the real source-cache location or equivalent test path.
- `/deploy-logs` mounted to a persistent log directory.
- `LAUNCHPAD_WEBHOOK_TOKEN` set.

For local testing without production paths, set `LAUNCHPAD_SOURCE_ROOT` to `./storage/sources` through Docker Compose defaults. The first local run will create/use:

```text
storage/sources/qa.fling.com
```

That local path is safe and does not touch production.

The first clone of `gpdev/www-fling-com` is large. `deployments/qa-fling.php` uses longer timeouts for that reason:

```php
set('git_clone_timeout', 3600);
set('git_fetch_timeout', 1800);
```

If a first clone times out, remove the incomplete local cache before retrying:

```bash
rm -rf storage/sources/qa.fling.com
```

Docker Compose uses separate host-path variables for mounts:

```env
LAUNCHPAD_SSH_HOST_PATH=./storage/ssh
LAUNCHPAD_SOURCE_HOST_PATH=./storage/sources
LAUNCHPAD_LOG_HOST_PATH=./storage/deploy-logs
```

The in-container Laravel paths stay:

```env
LAUNCHPAD_SOURCE_ROOT=/sources
LAUNCHPAD_LOG_ROOT=/deploy-logs
```
