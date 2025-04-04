<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\GoogleAuthController;

// Make root route explicit with GET method
Route::get('/', [CalendarController::class, 'index'])->name('home');

// Base routes
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
Route::post('/check-conflicts', [CalendarController::class, 'checkGuestConflicts'])->name('check-conflicts');

// Google Calendar OAuth routes
Route::get('/google/auth', [GoogleAuthController::class, 'redirect'])->name('google.auth');
Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
Route::get('/oauth/callback', [GoogleAuthController::class, 'callback'])->name('oauth.callback');
Route::get('/google/disconnect', [GoogleAuthController::class, 'disconnect'])->name('google.disconnect');
Route::get('/google/status', [GoogleAuthController::class, 'status'])->name('google.status');

// Google Calendar event CRUD routes - Make sure these match the paths used in JS
Route::post('/google/events', [CalendarController::class, 'storeGoogleEvent'])->name('google.events.store');
Route::post('/google/events/{eventId}', [CalendarController::class, 'updateGoogleEvent'])->name('google.events.update');
Route::delete('/google/events/{eventId}', [CalendarController::class, 'destroyGoogleEvent'])->name('google.events.destroy');

// Debug route to view session data
Route::get('/debug/session', function() {
    // Only enable in debug mode
    if (!config('app.debug')) {
        abort(404);
    }

    $sessionData = [];
    foreach (session()->all() as $key => $value) {
        if ($key === 'google_token') {
            // Sanitize token details for display
            $token = session('google_token');
            $sessionData[$key] = [
                'token_type' => $token['token_type'] ?? null,
                'expires_in' => $token['expires_in'] ?? null,
                'created' => $token['created'] ?? null,
                'has_access_token' => !empty($token['access_token']),
                'has_refresh_token' => !empty($token['refresh_token']),
                'scope' => $token['scope'] ?? null,
            ];
        } else {
            $sessionData[$key] = $value;
        }
    }

    return response()->json([
        'session_id' => session()->getId(),
        'data' => $sessionData
    ]);
})->name('debug.session');
