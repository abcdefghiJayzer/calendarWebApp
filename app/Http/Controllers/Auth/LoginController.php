<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Services\GoogleCalendarService;
use App\Http\Controllers\CalendarController;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Get the authenticated user
            $user = Auth::user();

            // Check if user has Google Calendar connected
            if ($user->google_access_token) {
                try {
                    // Initialize Google Calendar Service
                    $googleCalendarService = app(GoogleCalendarService::class);
                    
                    // Use the stored tokens from the database
                    $googleCalendarService->useUserTokens($user);

                    // Store the token in session
                    $token = json_decode($user->google_access_token, true);
                    session()->put('google_token', $token);
                    
                    // Create a new request instance for sync
                    $syncRequest = new Request();
                    
                    // Get the calendar controller instance
                    $calendarController = app(CalendarController::class);
                    
                    // Call the sync method and store result in session
                    $syncResult = $calendarController->syncAllToGoogle($syncRequest);
                    session()->flash('sync_result', $syncResult);
                    
                    \Log::info('Automatic sync completed after login', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'result' => $syncResult
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to auto-sync after login', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                    // Don't throw the error - we don't want to prevent login if sync fails
                }
            }

            return redirect()->intended('/');
        }

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
