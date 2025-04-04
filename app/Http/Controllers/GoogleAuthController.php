<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class GoogleAuthController extends Controller
{
    protected $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    public function redirect()
    {
        $authUrl = $this->googleCalendarService->getAuthUrl();
        Log::info('Redirecting to Google auth URL', ['url' => $authUrl]);

        // Store the current session ID to check for session loss
        Session::put('pre_auth_session_id', Session::getId());
        Log::info('Stored pre-auth session ID', ['id' => Session::getId()]);

        return redirect($authUrl);
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
                Log::info('Processing auth code', ['code' => substr($request->code, 0, 10) . '...']);
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

                return redirect()->route('home')->with('success', 'Successfully connected to Google Calendar!');
            } else {
                Log::error('No code in callback request', ['query' => $request->query()]);
                return redirect()->route('home')->with('error', 'Failed to connect to Google Calendar: No authorization code received');
            }
        } catch (\Exception $e) {
            Log::error('Google OAuth error', ['message' => $e->getMessage()]);
            return redirect()->route('home')->with('error', 'Failed to connect to Google Calendar: ' . $e->getMessage());
        }
    }

    public function disconnect()
    {
        Session::forget('google_token');
        Session::save();
        Log::info('Disconnected from Google Calendar');
        return redirect()->route('home')->with('success', 'Disconnected from Google Calendar.');
    }

    public function status()
    {
        $isAuthenticated = $this->googleCalendarService->isAuthenticated();
        Log::info('Google Calendar authentication status check', [
            'authenticated' => $isAuthenticated,
            'session_id' => Session::getId()
        ]);

        $sessionData = Session::has('google_token') ? 'exists' : 'missing';
        $tokenDetails = [];

        if (Session::has('google_token')) {
            $token = Session::get('google_token');
            $tokenDetails = [
                'token_type' => $token['token_type'] ?? 'unknown',
                'expires_in' => $token['expires_in'] ?? 'unknown',
                'created' => $token['created'] ?? 'unknown',
                'has_access_token' => isset($token['access_token']),
                'has_refresh_token' => isset($token['refresh_token']),
            ];
        }

        return response()->json([
            'authenticated' => $isAuthenticated,
            'session_token' => $sessionData,
            'token_details' => $tokenDetails
        ]);
    }
}
