# Local Launchpad Commands

## Start From Fresh Checkout

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

If any of those storage directories were created by Docker as root, fix local ownership:

```bash
sudo chown -R "$USER:$USER" storage/ssh storage/sources storage/deploy-logs
```

## Open App

```text
http://localhost:8080
```

## Watch Logs

```bash
docker compose logs -f web app worker scheduler mysql
```

## Stop App

```bash
docker compose down
```

## Start Existing App

```bash
docker compose up -d
```

## Rebuild After Dockerfile Changes

```bash
docker compose build --progress=plain
docker compose up -d
```

## Run Migrations Again

```bash
docker compose run --rm app php artisan migrate --seed
```

## Queue Worker Logs

```bash
docker compose logs -f worker
```

## Test Source Update Status

```bash
curl http://localhost:8080/source-updates
curl http://localhost:8080/source-updates/qa-fling
```
