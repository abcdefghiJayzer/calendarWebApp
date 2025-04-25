<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class GoogleAuthController extends Controller
{
    protected $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    public function redirect()
    {
        try {
            $authUrl = $this->googleCalendarService->getAuthUrl();
            Log::info('Redirecting to Google auth URL', ['url' => $authUrl]);

            // Store the current session ID to check for session loss
            Session::put('pre_auth_session_id', Session::getId());
            Log::info('Stored pre-auth session ID', ['id' => Session::getId()]);

            // Always store the current authenticated user ID
            if (Auth::check()) {
                Session::put('pre_auth_user_id', Auth::id());
                Log::info('Stored pre-auth user ID', ['user_id' => Auth::id()]);
            } else {
                Log::warning('No authenticated user when redirecting to Google');
            }

            // Save the session immediately
            Session::save();

            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('Error creating auth URL', ['error' => $e->getMessage()]);
            return redirect()->route('home')->with('error', 'Failed to connect to Google Calendar: ' . $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        try {
            Log::info('Google callback received', [
                'has_code' => $request->has('code'),
                'session_id' => Session::getId(),
                'pre_auth_session_id' => Session::get('pre_auth_session_id')
            ]);

            if ($request->has('code')) {
                Log::info('Processing auth code', ['code_prefix' => substr($request->code, 0, 5) . '...']);

                // Get the user ID from the session before any potential session issues
                $userId = Session::get('pre_auth_user_id');
                Log::info('Retrieved pre_auth_user_id', ['user_id' => $userId]);

                // Handle the auth callback to get the token
                $token = $this->googleCalendarService->handleAuthCallback($request->code);

                // Store token directly in session again to ensure it's saved
                Session::put('google_token', $token);
                Session::save();

                Log::info('OAuth successful, token stored in session', [
                    'session_id' => Session::getId(),
                    'token_type' => $token['token_type'] ?? 'none',
                    'expires_in' => $token['expires_in'] ?? 'none',
                    'has_access_token' => isset($token['access_token']),
                    'has_refresh_token' => isset($token['refresh_token'])
                ]);

                // Get the Google account email
                // IMPORTANT: This must be done right after setting the token
                $googleEmail = $this->googleCalendarService->getUserEmail();
                Log::info('Retrieved Google email', ['email' => $googleEmail]);

                if ($googleEmail) {
                    $user = null;

                    // First try using stored user ID
                    if ($userId) {
                        $user = \App\Models\User::find($userId);
                        Log::info('Found user from pre_auth_user_id', ['user_id' => $userId]);
                    }

                    // If that failed, try getting the currently authenticated user
                    if (!$user) {
                        $user = Auth::user();
                        Log::info('Using currently authenticated user', ['user_id' => $user?->id]);
                    }

                    // Update the user's google_calendar_id
                    if ($user) {
                        $user->google_calendar_id = $googleEmail;
                        $user->save();
                        Log::info('Successfully updated Google Calendar ID', [
                            'user_id' => $user->id,
                            'user_email' => $user->email,
                            'google_calendar_id' => $googleEmail
                        ]);
                    } else {
                        Log::error('No user found to update Google Calendar ID');
                    }
                } else {
                    Log::error('Failed to get Google email');
                }

                // Only use one flag to control the behavior - prevent duplicates
                return redirect()->route('home')->with([
                    'success' => 'Successfully connected to Google Calendar!',
                    'google_calendar_action' => 'enable_and_refresh'
                ]);
            } else {
                Log::error('No code in callback request', ['query' => $request->query()]);
                return redirect()->route('home')->with('error', 'Failed to connect to Google Calendar: No authorization code received');
            }
        } catch (\Exception $e) {
            Log::error('Google OAuth error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->route('home')->with('error', 'Failed to connect to Google Calendar: ' . $e->getMessage());
        }
    }

    public function disconnect()
    {
        // Make sure we completely remove any Google token data
        Session::forget('google_token');
        Session::forget('google_calendar_email');

        // Also remove any cached Google events data
        Session::forget('google_events');
        Session::forget('google_calendar_last_sync');
        Session::save();

        // Remove google_calendar_id from user
        $user = Auth::user();
        if ($user) {
            $user->google_calendar_id = null;
            $user->save();
        }

        Log::info('User disconnected from Google Calendar');

        return response()->json([
            'success' => true,
            'message' => 'Disconnected from Google Calendar.',
            'forceRefresh' => true
        ]);
    }

    public function status()
    {
        $isAuthenticated = $this->googleCalendarService->isAuthenticated();
        $userEmail = $this->googleCalendarService->getUserEmail();

        return response()->json([
            'authenticated' => $isAuthenticated,
            'email' => $userEmail,
            'sessionData' => Session::has('google_token') ? 'exists' : 'missing'
        ]);
    }
}
