<?php
use Illuminate\Support\Facades\Route;
use System\Agent\Http\Controllers\AgentController;

Route::middleware(['web'])->prefix('agent')->group(function () {
    // 1. The Dashboard View
    Route::get('/heartbeat', [AgentController::class, 'index'])->name('agent.dashboard');
    
    // 2. The AJAX Endpoint to fetch data
    Route::post('/fetch-model-data', [AgentController::class, 'fetchModelData'])->name('agent.fetch');
    Route::post('/get-schema', [AgentController::class, 'getSchema'])->name('agent.schema');
    Route::post('/store-data', [AgentController::class, 'store'])->name('agent.store');
    Route::post('/update-data', [AgentController::class, 'update'])->name('agent.update');
    Route::post('/delete-data', [AgentController::class, 'delete'])->name('agent.delete');
});

Route::get('/agent/status', function () {
    return response()->json(['status' => 'Agent module is active']);
});