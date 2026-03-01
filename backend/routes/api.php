<?php

use App\Modules\Simulation\Controllers\SimulationControlController;
use App\Modules\Simulation\Controllers\SimulationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/simulations', [SimulationController::class, 'listSimulations']);
    Route::get('/simulations/config/options', [SimulationController::class, 'configOptions']);
    Route::post('/simulations', [SimulationController::class, 'createSimulation']);
    Route::delete('/simulations/{simulation}', [SimulationController::class, 'deleteSimulation']);
    Route::post('/simulations/{simulation}/config', [SimulationController::class, 'updateSimulationConfig']);

    Route::post('/simulations/{simulation}/start', [SimulationControlController::class, 'start']);
    Route::post('/simulations/{simulation}/pause', [SimulationControlController::class, 'pause']);
    Route::post('/simulations/{simulation}/reset', [SimulationControlController::class, 'reset']);

    Route::get('/simulations/{simulation}/queue-preview', [SimulationControlController::class, 'queuePreview']);
    Route::post('/simulations/{simulation}/calls/manual', [SimulationControlController::class, 'enqueueManualCall']);
    Route::patch('/simulations/{simulation}/condition', [SimulationControlController::class, 'setSimulationCondition']);
    Route::patch('/simulations/{simulation}/elevators/{elevatorId}/condition', [SimulationControlController::class, 'setElevatorCondition']);
});
