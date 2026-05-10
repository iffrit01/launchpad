# Launchpad

Launchpad is the modern replacement for DeployUI.

It is a Laravel 12, PHP 8.3, Inertia, Vue 3, MySQL, and Deployer 8 application. The first migration target is source-update automation: GitLab webhooks request a source-cache update, Launchpad stores one state row per project, and a queue worker runs the update through Deployer.

## Local Start

```bash
cd /home/y/projects/launchpad

cp .env.example .env
mkdir -p storage/ssh storage/sources storage/deploy-logs

docker compose build
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate --seed
docker compose up -d
```

Open:

```text
http://localhost:8080
```

## Daily Commands

```bash
docker compose ps
docker compose logs -f web app worker scheduler mysql
docker compose down
docker compose up -d
```

## First Migrated Flow

`qa-fling` source updates are configured in:

- `config/projects.php`
- `deployments/qa-fling.php`

Manual request:

```bash
curl -X POST http://localhost:8080/projects/qa-fling/source-update
```

Webhook request:

```bash
curl -X POST \
  -H "X-Gitlab-Token: $LAUNCHPAD_WEBHOOK_TOKEN" \
  http://localhost:8080/webhooks/gitlab/qa-fling/source-update
```

Status:

```bash
curl http://localhost:8080/source-updates
curl http://localhost:8080/source-updates/qa-fling
```

The first local `qa-fling` source update may take a long time because it clones the full `gpdev/www-fling-com` repository. If it times out during the first clone, remove the incomplete cache and retry:

```bash
rm -rf storage/sources/qa.fling.com
curl -X POST http://localhost:8080/projects/qa-fling/source-update
```

## Runtime Mounts

- `/sources`: source repository cache root.
- `/deploy-logs`: source-update and deploy logs.
- `/home/launchpad/.ssh`: read-only SSH keys for GitLab and target hosts.

The local Docker Compose defaults map those to:

- `./storage/sources`
- `./storage/deploy-logs`
- `./storage/ssh`

For real server testing, point those environment variables at the real mounted paths.

Host mount paths and container paths are intentionally separate:

```env
LAUNCHPAD_SOURCE_HOST_PATH=./storage/sources
LAUNCHPAD_LOG_HOST_PATH=./storage/deploy-logs
LAUNCHPAD_SOURCE_ROOT=/sources
LAUNCHPAD_LOG_ROOT=/deploy-logs
```

## Documentation

- `docs/local-commands.md`
- `docs/qa-fling-source-update.md`
- `docs/launchpad-architecture.md`
