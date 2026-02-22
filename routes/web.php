<?php

use App\Http\Controllers\Analysis\IndexController as AnalysisIndexController;
use App\Http\Controllers\Analysis\RunSkillController;
use App\Http\Controllers\Runs\DestroyController as RunsDestroyController;
use App\Http\Controllers\Runs\IndexController as RunsIndexController;
use App\Http\Controllers\Runs\ShowController as RunsShowController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', RunsIndexController::class)->name('dashboard');
    Route::get('runs/{run}', RunsShowController::class)->name('runs.show');
    Route::delete('runs/{run}', RunsDestroyController::class)->name('runs.destroy');

    Route::get('analysis', AnalysisIndexController::class)->name('analysis.index');
    Route::post('analysis/{skill}', RunSkillController::class)->name('analysis.run');
});

require __DIR__.'/settings.php';
