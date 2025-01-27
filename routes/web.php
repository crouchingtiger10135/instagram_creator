<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ImageController;

Route::get('/', function () {
    return view('welcome');
});

// Require login + email verification for these
Route::middleware(['auth', 'verified'])->group(function () {
    // 1. Dashboard showing + uploading images
    Route::get('/dashboard', [ImageController::class, 'index'])->name('dashboard');
    Route::post('/dashboard', [ImageController::class, 'store'])->name('dashboard.store');

    // 2. Reorder images (drag-and-drop)
    Route::post('/dashboard/reorder', [ImageController::class, 'reorder'])->name('dashboard.reorder');

    // 3. Edit/update/delete specific images
    Route::get('/dashboard/images/{image}/edit', [ImageController::class, 'edit'])->name('dashboard.images.edit');
    Route::patch('/dashboard/images/{image}', [ImageController::class, 'update'])->name('dashboard.images.update');
    Route::delete('/dashboard/images/{image}', [ImageController::class, 'destroy'])->name('dashboard.images.destroy');

    // Breeze Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Breeze auth routes: /login, /register, etc.
require __DIR__.'/auth.php';
