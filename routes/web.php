<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarController;

// Shows calendar and fetch events
Route::get('/', [CalendarController::class, 'index'])->name('home');
Route::get('/events', [CalendarController::class, 'getEvents'])->name('getEvents');

// Show event details
Route::get('/events/{id}', [CalendarController::class, 'show'])->name('show');

// Create event
Route::get('/create', [CalendarController::class, 'create'])->name('create');
Route::post('/store', [CalendarController::class, 'store'])->name('store');

// Edit/Update event
Route::put('/events/{id}', [CalendarController::class, 'update'])->name('update');
Route::post('/events/{id}', [CalendarController::class, 'update']); // Handle POST requests with _method=PUT

// Delete event
Route::delete('/events/{id}', [CalendarController::class, 'destroy'])->name('destroy');

// Check for guest conflicts
Route::post('/check-conflicts', [CalendarController::class, 'checkGuestConflicts'])->name('checkGuestConflicts');

// Test route
Route::get('/test', function () {
    return 'Route is working!';
});
