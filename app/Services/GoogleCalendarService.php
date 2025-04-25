<?php

namespace App\Services;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Oauth2;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class GoogleCalendarService
{
    protected $client;
    protected $service;
    protected $calendarId;

    public function __construct()
    {
        try {
            // Initialize the client first, regardless of calendar ID status
            $this->client = new Google_Client();
            $this->client->setApplicationName('Calendar Web App');
            // Make sure we're using the full calendar scope, not just readonly
            $this->client->setScopes([
                \Google_Service_Calendar::CALENDAR,  // Full access
                'https://www.googleapis.com/auth/userinfo.email', // For getting user email
                'https://www.googleapis.com/auth/userinfo.profile', // For getting user profile
            ]);

            \Log::info('Google client initialized with scopes', [
                'scopes' => $this->client->getScopes()
            ]);

            // Check if credential file exists before using it
            $credentialsPath = storage_path('app/google/oauth-credentials.json');
            if (!file_exists($credentialsPath)) {
                Log::error('Google OAuth credentials file not found', ['path' => $credentialsPath]);
                throw new Exception("Google OAuth credentials file does not exist at: $credentialsPath");
            }

            $this->client->setAuthConfig($credentialsPath);
            $this->client->setAccessType('offline');
            $this->client->setPrompt('select_account consent');
            $this->client->setIncludeGrantedScopes(true);

            // Set the correct redirect URI - use absolute URL
            $redirectUri = url('/oauth/callback');
            Log::info('Setting Google OAuth redirect URI', ['uri' => $redirectUri]);
            $this->client->setRedirectUri($redirectUri);

            // Load previously authorized token if it exists
            if (Session::has('google_token')) {
                $token = Session::get('google_token');
                \Log::info('Found Google token in session', [
                    'token_type' => $token['token_type'] ?? 'unknown',
                    'expires_in' => $token['expires_in'] ?? 'unknown',
                    'created' => $token['created'] ?? 'unknown',
                ]);
                $this->client->setAccessToken($token);
            } else {
                \Log::info('No Google token in session');
            }

            // Refresh the token if needed
            if ($this->client->isAccessTokenExpired()) {
                \Log::info('Access token is expired');
                if ($this->client->getRefreshToken()) {
                    \Log::info('Refreshing expired Google token with refresh token');
                    $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                    $newToken = $this->client->getAccessToken();
                    // Store the new token in session
                    Session::put('google_token', $newToken);
                    \Log::info('Token refreshed successfully', [
                        'new_token' => [
                            'token_type' => $newToken['token_type'] ?? 'unknown',
                            'expires_in' => $newToken['expires_in'] ?? 'unknown',
                            'created' => $newToken['created'] ?? 'unknown',
                        ]
                    ]);
                } else {
                    \Log::warning('Google token is expired and no refresh token available');
                }
            }

            // Initialize the Calendar service
            $this->service = new \Google_Service_Calendar($this->client);

            // Check if we're in an OAuth callback or authentication flow
            $isInAuthFlow = request()->is('oauth/callback') ||
                           request()->is('google/auth') ||
                           request()->is('google/callback');

            // Check if we're on a page that specifically needs Google Calendar data
            $needsCalendar = request()->is('events*') ||
                            request()->routeIs('events.*') ||
                            request()->ajax();

            // Set the calendar ID - always use 'primary' to access the user's default calendar
            if ($this->isAuthenticated()) {
                // If authenticated, always use 'primary' for the user's main calendar
                $this->calendarId = 'primary';
                \Log::info('Using "primary" as calendar ID for authenticated user');
            } else if ($needsCalendar) {
                // We're on a calendar page but not authenticated
                // Just use primary as placeholder - methods will handle auth requirements
                $this->calendarId = 'primary';
                \Log::info('No Google Calendar auth but on calendar page - using placeholder ID');
            } else {
                // Not authenticated and not on calendar page
                $this->calendarId = null;
                \Log::info('No Google Calendar authentication found, calendar ID set to null');
            }

            // Still store the google_calendar_id in the user record for reference
            if ($this->isAuthenticated()) {
                $user = Auth::user();
                if ($user && !$user->google_calendar_id) {
                    try {
                        $email = $this->getUserEmail();
                        if ($email) {
                            $user->google_calendar_id = $email;
                            $user->save();
                            \Log::info('Auto-saved Google Calendar ID', [
                                'user_id' => $user->id,
                                'google_calendar_id' => $email
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Failed to auto-save Google Calendar ID', ['error' => $e->getMessage()]);
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Error initializing Google Calendar Service', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getAuthUrl()
    {
        $authUrl = $this->client->createAuthUrl();
        Log::info('Generated Google auth URL', ['url' => $authUrl]);
        return $authUrl;
    }

    public function handleAuthCallback($code)
    {
        try {
            Log::info('Handling Google auth callback');
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
            $this->client->setAccessToken($accessToken);

            // Check for errors
            if (array_key_exists('error', $accessToken)) {
                Log::error('Error in access token response', ['error' => $accessToken['error']]);
                throw new Exception(join(', ', $accessToken));
            }

            Log::info('Successfully obtained access token', [
                'token_type' => $accessToken['token_type'],
                'expires_in' => $accessToken['expires_in'],
                'has_refresh_token' => isset($accessToken['refresh_token'])
            ]);

            Session::put('google_token', $accessToken);
            return $accessToken;
        } catch (Exception $e) {
            Log::error('Exception handling auth callback', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function isAuthenticated()
    {
        try {
            $hasToken = Session::has('google_token');

            if (!$hasToken) {
                Log::info('No Google token in session, not authenticated');
                return false;
            }

            // Check if token is valid
            $tokenData = Session::get('google_token');
            Log::info('Checking authentication with token', [
                'token_type' => $tokenData['token_type'] ?? 'unknown',
                'expires_in' => $tokenData['expires_in'] ?? 'unknown',
                'created' => $tokenData['created'] ?? 'unknown',
                'has_access_token' => !empty($tokenData['access_token']),
            ]);

            $this->client->setAccessToken($tokenData);
            $notExpired = !$this->client->isAccessTokenExpired();

            if (!$notExpired && isset($tokenData['refresh_token'])) {
                Log::info('Token expired but has refresh token, attempting refresh');
                $this->client->fetchAccessTokenWithRefreshToken($tokenData['refresh_token']);
                $newToken = $this->client->getAccessToken();
                Session::put('google_token', $newToken);
                $notExpired = true;
                Log::info('Token refreshed successfully');
            }

            Log::info('Google Calendar authentication status', [
                'has_token' => $hasToken,
                'not_expired' => $notExpired
            ]);

            return $hasToken && $notExpired;
        } catch (Exception $e) {
            Log::error('Error checking authentication status', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getEvents($options = [])
    {
        try {
            if (!$this->isAuthenticated()) {
                Log::warning('Not authenticated with Google Calendar when trying to get events');
                return [];
            }

            if (!$this->calendarId) {
                Log::warning('No Calendar ID available when trying to get events');
                return [];
            }

            Log::info('Fetching Google events with calendar ID: ' . $this->calendarId, [
                'options' => $options,
                'token_exists' => Session::has('google_token'),
                'user_id' => Auth::id()
            ]);

            $results = $this->service->events->listEvents($this->calendarId, $options);

            $itemCount = count($results->getItems());
            Log::info('Successfully retrieved Google Calendar events', [
                'count' => $itemCount,
                'calendar_id' => $this->calendarId
            ]);

            return $results->getItems();
        } catch (\Exception $e) {
            Log::error('Error getting Google Calendar events', [
                'error' => $e->getMessage(),
                'calendar_id' => $this->calendarId ?? 'none',
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    public function getEvent($eventId)
    {
        if (!$this->isAuthenticated() || !$this->calendarId) {
            return null;
        }

        return $this->service->events->get($this->calendarId, $eventId);
    }

    public function createEvent($data)
    {
        if (!$this->isAuthenticated()) {
            throw new Exception('Not authenticated with Google Calendar');
        }

        if (!$this->calendarId) {
            throw new Exception('No Google Calendar ID available');
        }

        $event = new \Google_Service_Calendar_Event([
            'summary' => $data['title'],
            'description' => $data['description'] ?? '',
            'start' => [
                'dateTime' => date('c', strtotime($data['start_date'])),
                'timeZone' => config('app.timezone'),
            ],
            'end' => [
                'dateTime' => date('c', strtotime($data['end_date'])),
                'timeZone' => config('app.timezone'),
            ],
            'location' => $data['location'] ?? '',
            'colorId' => $this->mapColorToGoogleColorId($data['color'] ?? '#3b82f6'),
        ]);

        if (!empty($data['guests'])) {
            $attendees = [];
            foreach ($data['guests'] as $email) {
                $attendees[] = ['email' => $email];
            }
            $event->setAttendees($attendees);
        }

        return $this->service->events->insert($this->calendarId, $event);
    }

    public function updateEvent($eventId, $data)
    {
        if (!$this->isAuthenticated()) {
            throw new Exception('Not authenticated with Google Calendar');
        }

        if (!$this->calendarId) {
            throw new Exception('No Google Calendar ID available');
        }

        try {
            $event = $this->service->events->get($this->calendarId, $eventId);

            $event->setSummary($data['title']);
            if (isset($data['description'])) {
                $event->setDescription($data['description']);
            }

            $event->setStart([
                'dateTime' => date('c', strtotime($data['start_date'])),
                'timeZone' => config('app.timezone'),
            ]);

            $event->setEnd([
                'dateTime' => date('c', strtotime($data['end_date'])),
                'timeZone' => config('app.timezone'),
            ]);

            if (isset($data['location'])) {
                $event->setLocation($data['location']);
            }

            if (isset($data['color'])) {
                $event->setColorId($this->mapColorToGoogleColorId($data['color']));
            }

            if (!empty($data['guests'])) {
                $attendees = [];
                foreach ($data['guests'] as $email) {
                    $attendees[] = ['email' => $email];
                }
                $event->setAttendees($attendees);
            }

            \Log::info('Updating Google Calendar event', [
                'eventId' => $eventId,
                'calendarId' => $this->calendarId,
                'title' => $data['title']
            ]);

            $updatedEvent = $this->service->events->update($this->calendarId, $eventId, $event);
            \Log::info('Google Calendar event updated successfully', ['eventId' => $eventId]);

            return $updatedEvent;
        } catch (\Google\Service\Exception $e) {
            $error = json_decode($e->getMessage(), true);
            \Log::error('Google API error when updating event', [
                'eventId' => $eventId,
                'calendarId' => $this->calendarId,
                'error' => $error,
                'message' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error updating Google Calendar event', [
                'eventId' => $eventId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deleteEvent($eventId)
    {
        if (!$this->isAuthenticated()) {
            throw new Exception('Not authenticated with Google Calendar');
        }

        if (!$this->calendarId) {
            throw new Exception('No Google Calendar ID available');
        }

        try {
            \Log::info('Deleting Google Calendar event', [
                'eventId' => $eventId,
                'calendarId' => $this->calendarId
            ]);

            $result = $this->service->events->delete($this->calendarId, $eventId);
            \Log::info('Google Calendar event deleted successfully', ['eventId' => $eventId]);

            return $result;
        } catch (\Google\Service\Exception $e) {
            $error = json_decode($e->getMessage(), true);
            \Log::error('Google API error when deleting event', [
                'eventId' => $eventId,
                'calendarId' => $this->calendarId,
                'error' => $error,
                'message' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error deleting Google Calendar event', [
                'eventId' => $eventId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // Map custom colors to Google Calendar color IDs
    private function mapColorToGoogleColorId($hexColor)
    {
        // Google Calendar color IDs: https://developers.google.com/calendar/api/v3/reference/colors/get
        $colorMap = [
            '#3b82f6' => '1', // Blue
            '#ef4444' => '11', // Red
            '#eab308' => '5', // Yellow
            '#22c55e' => '2', // Green
            '#000000' => '8', // Black/Gray
        ];

        return $colorMap[$hexColor] ?? '1'; // Default to blue (1) if no match
    }

    public function getUserEmail()
    {
        try {
            // Check if we have a token first
            if (!Session::has('google_token')) {
                Log::warning('Cannot get user email - no token in session');
                return null;
            }

            $token = Session::get('google_token');
            // Make sure we're using the token properly
            $this->client->setAccessToken($token);

            // Create a new OAuth2 service for user info
            $oauth2 = new \Google_Service_Oauth2($this->client);
            $userInfo = $oauth2->userinfo->get();
            $email = $userInfo->getEmail();

            if (empty($email)) {
                Log::warning('Retrieved empty Google user email');
                return null;
            }

            Log::info('Successfully retrieved Google user email', ['email' => $email]);

            // Additional debug for flow tracking
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : 'unknown';
            Log::info('getUserEmail called from', ['caller' => $caller]);

            return $email;
        } catch (\Exception $e) {
            Log::error('Error getting Google user email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}
