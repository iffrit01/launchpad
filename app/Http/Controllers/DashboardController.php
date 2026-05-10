<?php

namespace App\Http\Controllers;

use App\Support\ProjectViewData;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return inertia('Dashboard', [
            'projects' => ProjectViewData::sourceUpdateProjects(),
        ]);
    }
}
