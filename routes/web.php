<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GitlabWebhookController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\SourceUpdateController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('/releases', [ReleaseController::class, 'index']);
Route::get('/projects/{project:slug}/releases/commits', [ReleaseController::class, 'commits']);
Route::get('/services/source-updates', [SourceUpdateController::class, 'page']);

Route::get('/source-updates', [SourceUpdateController::class, 'index']);
Route::get('/source-updates/{project}', [SourceUpdateController::class, 'index']);
Route::get('/projects/{project:slug}/source-update/log', [SourceUpdateController::class, 'log']);
Route::post('/projects/{project:slug}/source-update', [SourceUpdateController::class, 'store']);
Route::post('/webhooks/gitlab/{project:slug}/source-update', [GitlabWebhookController::class, 'sourceUpdate']);
