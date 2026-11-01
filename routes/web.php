<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchDateOverrideController;
use App\Http\Controllers\BusinessController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/branches');
Route::get('/businesses/data', [BusinessController::class, 'data'])->name('businesses.data');
Route::resource('businesses', BusinessController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
Route::get('/branches/data', [BranchController::class, 'data'])->name('branches.data');
Route::get('/branches/{branch}/status', [BranchController::class, 'status'])->name('branches.status');
Route::post('/branches/{branch}/date-overrides', [BranchDateOverrideController::class, 'store'])->name('branches.date-overrides.store');
Route::delete('/branches/{branch}/date-overrides/{dateOverride}', [BranchDateOverrideController::class, 'destroy'])->name('branches.date-overrides.destroy');
Route::resource('branches', BranchController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
