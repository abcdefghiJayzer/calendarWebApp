<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\GoogleAuthController;

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
});

// Protect calendar routes with auth middleware
Route::middleware('auth')->group(function () {
    Route::get('/', [CalendarController::class, 'index'])->name('home');
    Route::get('/events', [CalendarController::class, 'getEvents'])->name('getEvents');
    Route::get('/events/{id}', [CalendarController::class, 'show'])->name('events.show');
    Route::post('/events', [CalendarController::class, 'store'])->name('events.store');
    Route::post('/events/{id}', [CalendarController::class, 'update'])->name('events.update');
    Route::delete('/events/{id}', [CalendarController::class, 'destroy'])->name('events.destroy');
    Route::post('/check-conflicts', [CalendarController::class, 'checkGuestConflicts'])->name('check-conflicts');

    // Google Calendar OAuth routes
    Route::get('/google/auth', [GoogleAuthController::class, 'redirect'])->name('google.auth');
    Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
    Route::get('/oauth/callback', [GoogleAuthController::class, 'callback'])->name('oauth.callback');
    Route::get('/google/disconnect', [GoogleAuthController::class, 'disconnect'])->name('google.disconnect');
    Route::get('/google/status', [GoogleAuthController::class, 'status'])->name('google.status');

    // Google Calendar event CRUD routes
    Route::post('/google/events', [CalendarController::class, 'storeGoogleEvent'])->name('google.events.store');
    Route::post('/google/events/{eventId}', [CalendarController::class, 'updateGoogleEvent'])->name('google.events.update');
    Route::delete('/google/events/{eventId}', [CalendarController::class, 'destroyGoogleEvent'])->name('google.events.destroy');

    // Sync local event to Google Calendar
    Route::post('/events/{id}/sync-to-google', [CalendarController::class, 'syncToGoogle'])->name('events.sync-to-google');

    // Sync all local events to Google Calendar
    Route::post('/sync-all-to-google', [CalendarController::class, 'syncAllToGoogle'])->name('events.sync-all-to-google');
});

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
