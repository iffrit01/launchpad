# Launchpad Architecture

Launchpad is the planned replacement for the current DeployUI deployment tool.

## Stack

- Backend: Laravel 12 on PHP 8.3.
- UI: Inertia + Vue 3 + Vite.
- Deployment engine: Deployer 8.
- Queue/state: MySQL and Laravel database queues.
- Runtime: Docker Compose with separate app, web, worker, scheduler, and mysql services.
- Logs: deployment logs written to mounted files, with database rows pointing at log paths.
- Secrets: GitLab webhook token required; SSH keys mounted read-only into the PHP containers.

## Why Docker

The old tool depends heavily on the exact runtime inside the deployment container: PHP version, SSH keys, git, composer, npm, Deployer, and mounted source-cache paths. Keeping Launchpad in Docker avoids relying on whichever PHP and Composer versions happen to exist on the host.

Local testing and production should use the same image shape. The host only needs Docker, Git, and Docker Compose.

## Update Model

Launchpad should not deploy itself. It should be updated with a simple external server procedure:

1. Pull the Launchpad repository on the server.
2. Rebuild the app image.
3. Run migrations.
4. Restart the compose services.

This avoids a bootstrapping problem where the deployment tool controls its own availability.

## Initial Migration Scope

The first production feature should be source-update automation, because it is already understood from the current DeployUI queue work:

- One row per project.
- `pending`, `running`, `success`, and `fail` states.
- `rerun` flag when another webhook arrives while the same project is already running.
- No duplicate concurrent source-update job for the same project.
- Webhook endpoint protected by `X-Gitlab-Token`.
- Worker supervised by Docker restart policy.

Full deploy flows should be migrated project-by-project after source updates are stable.

## Implemented Foundation

The initial scaffold already includes:

- `projects` table for project configuration.
- `source_update_states` table for one-row-per-project update state.
- Manual source-update route: `POST /projects/{project}/source-update`.
- GitLab webhook route: `POST /webhooks/gitlab/{project}/source-update`.
- Status route for all projects: `GET /source-updates`.
- Status route for one project: `GET /source-updates/{project}`.
- `RunSourceUpdate` queue job.
- `SourceUpdateService` with rerun handling and per-project state claiming.
- `DeployerRunner` wrapper around `vendor/bin/dep`.
- Dashboard page showing project source-update states.

## Source Paths

Expected mounts:

- `/sources`: project source caches.
- `/deploy-logs`: deployment and source-update log files.
- `/home/launchpad/.ssh`: read-only SSH key mount for GitLab and target hosts.

## Next Implementation Slices

1. Port one existing Deployer project recipe into `deployments/`.
2. Add authenticated users and static admin access.
3. Add project create/edit screens.
4. Add log viewer pages for source updates.
5. Add a reconciler command for stuck `running` states.
6. Add deployment flow migration for one pilot project.
