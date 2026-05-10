<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('projects') as $project) {
            Project::query()->updateOrCreate(
                ['slug' => $project['slug']],
                $project,
            );
        }
    }
}
