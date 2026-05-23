<?php

use App\Http\Controllers\Analysis\ChatController as AnalysisChatController;
use App\Http\Controllers\Analysis\ConfirmActionController as AnalysisConfirmActionController;
use App\Http\Controllers\Analysis\IndexController as AnalysisIndexController;
use App\Http\Controllers\Analysis\RunSkillController;
use App\Http\Controllers\Runs\DestroyController as RunsDestroyController;
use App\Http\Controllers\Runs\IndexController as RunsIndexController;
use App\Http\Controllers\Runs\ShowController as RunsShowController;
use App\Http\Controllers\Runs\UpdateController as RunsUpdateController;
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
    Route::patch('runs/{run}', RunsUpdateController::class)->name('runs.update');
    Route::delete('runs/{run}', RunsDestroyController::class)->name('runs.destroy');

    Route::get('analysis', AnalysisIndexController::class)->name('analysis.index');
    Route::post('analysis/chat', [AnalysisChatController::class, 'stream'])->name('analysis.chat');
    Route::post('analysis/actions/{id}/confirm', AnalysisConfirmActionController::class)
        ->whereUuid('id')
        ->name('analysis.actions.confirm');
    Route::post('analysis/{skill}', RunSkillController::class)->name('analysis.run');
});

require __DIR__.'/settings.php';
