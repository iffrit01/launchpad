# Deployment Recipes

Project Deployer recipes live here.

The first migration should port one existing DeployUI recipe, then configure it in `config/projects.php`:

```php
[
    'slug' => 'qa-fling',
    'name' => 'QA Fling',
    'deployer_file' => 'deployments/qa-fling.php',
    'source_update_task' => 'deploy:update-cache-qa',
    'stage' => 'production',
    'active' => true,
],
```

Launchpad runs the recipe with:

```bash
vendor/bin/dep --file=deployments/qa-fling.php deploy:update-cache-qa -vvv -- production
```
