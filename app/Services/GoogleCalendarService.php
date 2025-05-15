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

    /**
     * Get the Google Client instance
     *
     * @return Google_Client
     */
    public function getClient()
    {
        return $this->client;
    }

    public function useUserTokens(\App\Models\User $user)
    {
        if (empty($user->google_access_token)) {
            Log::warning('No Google access token available for user', ['user_id' => $user->id]);
            return false;
        }

        try {
            $client = $this->getClient();

            // Set access token from database
            $accessToken = json_decode($user->google_access_token, true);
            $client->setAccessToken($accessToken);

            // Check if token needs refresh
            if ($client->isAccessTokenExpired()) {
                Log::info('Google access token expired, refreshing', ['user_id' => $user->id]);

                if ($client->getRefreshToken()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

                    // Save the new access token to the database
                    $user->google_access_token = json_encode($client->getAccessToken());
                    $user->save();

                    Log::info('Google access token refreshed successfully', ['user_id' => $user->id]);
                } else {
                    Log::error('No refresh token available', ['user_id' => $user->id]);
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error using user tokens: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function isAuthenticated()
    {
        try {
            $user = Auth::user();

            // Check if the user has tokens stored in the database
            if (!$user || empty($user->google_access_token)) {
                return false;
            }

            // Attempt to use the stored tokens
            return $this->useUserTokens($user);
        } catch (\Exception $e) {
            Log::error('Error checking Google authentication: ' . $e->getMessage());
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

        $event = new \Google_Service_Calendar_Event();
        $event->setSummary($data['title']);
        $event->setDescription($data['description'] ?? '');

        // Create proper EventDateTime objects for start and end dates
        $startDateTime = new \Google_Service_Calendar_EventDateTime();
        $startDateTime->setDateTime(date('c', strtotime($data['start_date'])));
        $startDateTime->setTimeZone(config('app.timezone'));
        $event->setStart($startDateTime);

        $endDateTime = new \Google_Service_Calendar_EventDateTime();
        $endDateTime->setDateTime(date('c', strtotime($data['end_date'])));
        $endDateTime->setTimeZone(config('app.timezone'));
        $event->setEnd($endDateTime);

        $event->setLocation($data['location'] ?? '');
        $event->setColorId($this->mapColorToGoogleColorId($data['color'] ?? '#3b82f6'));

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

            // Create proper EventDateTime objects for start and end dates
            $startDateTime = new \Google_Service_Calendar_EventDateTime();
            $startDateTime->setDateTime(date('c', strtotime($data['start_date'])));
            $startDateTime->setTimeZone(config('app.timezone'));
            $event->setStart($startDateTime);

            $endDateTime = new \Google_Service_Calendar_EventDateTime();
            $endDateTime->setDateTime(date('c', strtotime($data['end_date'])));
            $endDateTime->setTimeZone(config('app.timezone'));
            $event->setEnd($endDateTime);

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
            '#33b679' => '2', // Admin sector head (Green)
            '#039be5' => '1', // Research/Development sector head (Blue)
            '#e8b4bc' => '4', // Division head (Pink)
            '#616161' => '8', // Division employee (Gray)
            '#3b82f6' => '1', // Default blue
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

    /**
     * Sync multiple local events to Google Calendar
     *
     * @return array Results of sync operation with success, skipped and failed events
     */
    public function syncMultipleEvents()
    {
        if (!$this->isAuthenticated()) {
            throw new Exception('Not authenticated with Google Calendar');
        }

        if (!$this->calendarId) {
            throw new Exception('No Google Calendar ID available');
        }

        $results = [
            'success' => [],
            'skipped' => [],
            'failed' => []
        ];

        try {
            // Get events created by the current user
            // Use chunking to handle large datasets without memory issues
            $user_id = auth()->id();
            $eventCount = 0;

            \App\Models\Event::where('user_id', $user_id)
                ->with('participants')
                ->chunk(50, function($events) use (&$results, &$eventCount) {
                    foreach ($events as $event) {
                        $eventCount++;

                        try {
                            // Check if this event already has a Google ID but verify the event still exists in Google
                            if ($event->google_event_id) {
                                try {
                                    // Try to retrieve the event from Google to see if it exists
                                    $googleEvent = $this->getEvent($event->google_event_id);

                                    if ($googleEvent) {
                                        // Event exists in Google, skip it
                                        $results['skipped'][] = [
                                            'id' => $event->id,
                                            'title' => $event->title,
                                            'reason' => 'Already synced with Google Calendar'
                                        ];
                                        continue;
                                    } else {
                                        // Google event doesn't exist anymore, we need to create it again
                                        \Log::info('Event exists in DB with Google ID, but not in Google Calendar; will recreate', [
                                            'event_id' => $event->id,
                                            'google_event_id' => $event->google_event_id
                                        ]);
                                        // Continue with creation (don't skip)
                                    }
                                } catch (\Exception $e) {
                                    // Error checking Google event - assume it's gone and we need to recreate
                                    \Log::warning('Error checking if Google event exists, will attempt to recreate', [
                                        'event_id' => $event->id,
                                        'google_event_id' => $event->google_event_id,
                                        'error' => $e->getMessage()
                                    ]);
                                    // Continue with creation (don't skip)
                                }
                            }

                            // Prepare event data for Google Calendar with proper formatting
                            $eventData = [
                                'title' => $event->title,
                                'description' => $event->description,
                                'start_date' => $event->start_date,
                                'end_date' => $event->end_date ?: $event->start_date, // Use start_date as fallback
                                'location' => $event->location,
                                'color' => $event->color,
                                'guests' => $event->participants->pluck('email')->toArray(),
                                'is_all_day' => $event->is_all_day
                            ];

                            // Add some debugging to track any invalid dates
                            $startTimestamp = strtotime($eventData['start_date']);
                            $endTimestamp = strtotime($eventData['end_date']);

                            if (!$startTimestamp || !$endTimestamp) {
                                throw new \Exception("Invalid date format: Start: {$eventData['start_date']}, End: {$eventData['end_date']}");
                            }

                            // Create the event in Google Calendar
                            $googleEvent = $this->createEvent($eventData);

                            // Update the local event with the Google event ID
                            $event->google_event_id = $googleEvent->id;
                            $event->save();

                            $results['success'][] = [
                                'id' => $event->id,
                                'title' => $event->title,
                                'google_event_id' => $googleEvent->id
                            ];

                            \Log::info('Event synced to Google Calendar', [
                                'event_id' => $event->id,
                                'title' => $event->title,
                                'google_event_id' => $googleEvent->id
                            ]);
                        } catch (\Exception $e) {
                            \Log::error('Failed to sync event to Google Calendar', [
                                'event_id' => $event->id,
                                'title' => $event->title,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);

                            $results['failed'][] = [
                                'id' => $event->id,
                                'title' => $event->title,
                                'error' => $e->getMessage()
                            ];
                        }
                    }
                });

            \Log::info('Bulk sync completed', [
                'total_events' => $eventCount,
                'success_count' => count($results['success']),
                'skipped_count' => count($results['skipped']),
                'failed_count' => count($results['failed'])
            ]);

            return $results;

        } catch (\Exception $e) {
            \Log::error('Error in bulk sync operation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Ensure we have valid tokens by refreshing from database if needed
     */
    public function refreshTokenIfNeeded()
    {
        // Only proceed if we have a logged-in user
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // Check if the user has Google tokens stored in the database
        if ($user->google_access_token && $user->google_refresh_token) {
            try {
                // Load tokens from database to the client
                $client = $this->getClient();
                $client->setAccessToken(json_decode($user->google_access_token, true));

                // If token is expired, refresh it
                if ($client->isAccessTokenExpired()) {
                    \Log::info('Refreshing expired Google token for user', ['user_id' => $user->id]);

                    if ($client->getRefreshToken()) {
                        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

                        // Save the new access token to the database
                        $user->google_access_token = json_encode($client->getAccessToken());
                        $user->save();

                        \Log::info('Google token refreshed and saved to database');
                    } else {
                        \Log::warning('No refresh token available for user', ['user_id' => $user->id]);
                    }
                }

                return true;
            } catch (\Exception $e) {
                \Log::error('Error refreshing Google token: ' . $e->getMessage());
                return false;
            }
        }

        return false;
    }
}
