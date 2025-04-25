<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventGuest;
use App\Services\GoogleCalendarService;

class CalendarController extends Controller
{
    protected $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
        // Apply division middleware to store and update methods
        $this->middleware('division')->only(['store', 'update']);
    }

    public function index(Request $request)
    {
        $googleApiKey = config('services.google.calendar.api_key');
        $googleCalendarId = config('services.google.calendar.calendar_id');
        $isGoogleAuthenticated = $this->googleCalendarService->isAuthenticated();

        // Log Google Calendar settings for debugging
        \Log::info('Google Calendar settings', [
            'api_key' => $googleApiKey ? 'Set' : 'Not set',
            'calendar_id' => $googleCalendarId,
            'oauth_authenticated' => $isGoogleAuthenticated
        ]);

        return view('calendar', compact('googleApiKey', 'googleCalendarId', 'isGoogleAuthenticated'));
    }

    public function getEvents()
    {
        $user = auth()->user();
        $isAdmin = $user->division === 'institute';
        $isDivisionHead = $user->is_division_head;

        // Start with a base query
        $eventsQuery = Event::with('participants');

        if ($isAdmin) {
            // Admin sees all events
            $eventsQuery->where('calendar_type', 'institute');
        } else if ($isDivisionHead) {
            // Division heads see institute events, their sector events, and their division events
            $userSector = explode('_', $user->division)[0];
            $eventsQuery->where(function($query) use ($user, $userSector) {
                $query->where('calendar_type', 'institute')
                      ->orWhere('calendar_type', $userSector)
                      ->orWhere('calendar_type', $user->division);
            });
        } else {
            // Regular users only see institute events and their division events
            $eventsQuery->where(function($query) use ($user) {
                $query->where('calendar_type', 'institute')
                      ->orWhere('calendar_type', $user->division);
            });
        }

        $events = $eventsQuery->select(
            'id',
            'title',
            'start_date as start',
            'end_date as end',
            'location',
            'color as backgroundColor',
            'description',
            'is_all_day as allDay',
            'calendar_type',
            'private',
            'user_id'
        )
        ->get()
        ->map(function ($event) {
            $data = $event->toArray();

            // Map database calendar_type values to filter values
            $calendarTypeMap = [
                'Institute Level' => 'institute',
                'Sectoral' => 'sectoral',
                'Sector 1' => 'sector1',
                'Sector 2' => 'sector2',
                'Sector 3' => 'sector3',
                'Sector 4' => 'sector4',
                'Division 1' => 'sector1_div1', // Default to Sector 1's Division 1
            ];

            // Get the mapped calendar type or keep original if no mapping exists
            $mappedCalendarType = $calendarTypeMap[$event->calendar_type] ?? $event->calendar_type;

            // Hide details if event is private and user is not the owner
            if ($event->private && $event->user_id !== auth()->id()) {
                $data['title'] = 'Private Event';
                $data['backgroundColor'] = '#808080'; // Grey color for private events
                $data['extendedProps'] = [
                    'description' => 'Private event - Details hidden',
                    'location' => null,
                    'guests' => [],
                    'calendarType' => $mappedCalendarType,
                    'private' => true,
                    'user_id' => $event->user_id
                ];
            } else {
                $data['extendedProps'] = [
                    'description' => $event->description,
                    'location' => $event->location,
                    'guests' => $event->participants->pluck('email'),
                    'calendarType' => $mappedCalendarType,
                    'private' => $event->private,
                    'user_id' => $event->user_id
                ];
            }

            return $data;
        });

        // Get Google Calendar events and combine with local events
        $googleEvents = $this->getGoogleEvents();

        // Log both events for debugging
        \Log::info('Local events count: ' . count($events));
        \Log::info('Google events count: ' . count($googleEvents));

        // Merge arrays
        if (!empty($googleEvents)) {
            $events = $events->concat($googleEvents);
        }

        return response()->json($events);
    }

    public function show($id)
    {
        $event = Event::with('participants')->find($id);

        if (!$event) {
            return response()->json(['error' => 'Event not found'], 404);
        }

        return response()->json([
            'id' => $event->id,
            'title' => $event->title,
            'start' => $event->start_date,
            'end' => $event->end_date,
            'allDay' => $event->is_all_day,
            'backgroundColor' => $event->color,
            'description' => $event->description,
            'location' => $event->location,
            'calendar_type' => $event->calendar_type,
            'private' => $event->private,
            'user_id' => $event->user_id,
            'guests' => $event->participants->pluck('email'),
            'extendedProps' => [
                'description' => $event->description,
                'location' => $event->location,
                'guests' => $event->participants->pluck('email'),
                'calendar_type' => $event->calendar_type,
                'private' => $event->private,
                'user_id' => $event->user_id
            ]
        ]);
    }

    public function create()
    {
        // Get user's division to pass to view for pre-selecting
        $userDivision = auth()->user()->division;
        return view('add', compact('userDivision'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|min:1',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'location' => 'nullable|string',
                'color' => 'required|string',
                'guests' => 'nullable|string', // JSON string of guest emails
                'is_all_day' => 'nullable|boolean',
                'calendar_type' => 'required|in:institute,sector1,sector1_div1,sector2,sector2_div1,sector3,sector3_div1,sector4,sector4_div1',
            ]);

            // Check if the user has permission to create events in this division
            $user = auth()->user();
            $calendarType = $request->calendar_type;

            if (!$user->canCreateEventsIn($calendarType)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to create events in this division.'
                ], 403);
            }

            $event = Event::create([
                'title' => $request->title,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'location' => $request->location,
                'user_id' => auth()->id(),
                'is_all_day' => $request->is_all_day ?? false,
                'status' => $request->status ?? 'pending',
                'color' => $request->color,
                'calendar_type' => $calendarType,
                'private' => $request->boolean('private'),
            ]);

            // Handle guests
            $guestEmails = json_decode($request->guests, true) ?? [];

            foreach ($guestEmails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $guest = EventGuest::firstOrCreate(['email' => $email]);
                    $event->participants()->attach($guest->id);
                }
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Event created successfully']);
            }

            return redirect()->route('home')->with('success', 'Event created successfully!');
        } catch (\Exception $e) {
            \Log::error('Event creation error: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Failed to create event: ' . $e->getMessage());
        }
    }

    public function edit(string $id)
    {
        $event = Event::findOrFail($id);

        // Check if user can edit this event based on division
        if (!auth()->user()->canCreateEventsIn($event->calendar_type)) {
            return redirect()->route('home')->with('error', 'You do not have permission to edit this event.');
        }

        return view('edit', compact('event'));
    }

    public function update(Request $request, string $id)
    {
        try {
            $event = Event::findOrFail($id);

            // Check if user can edit this event based on division
            if (!auth()->user()->canCreateEventsIn($event->calendar_type) &&
                !auth()->user()->canCreateEventsIn($request->calendar_type)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to edit this event or change to this division.'
                ], 403);
            }

            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'guests' => 'nullable|json',
                'location' => 'nullable|string|max:255',
                'color' => 'required|string|max:20',
                'is_all_day' => 'boolean',
                'calendar_type' => 'required|in:institute,sector1,sector1_div1,sector2,sector2_div1,sector3,sector3_div1,sector4,sector4_div1',
            ]);

            // Properly handle the is_all_day checkbox (might come as "0", "1", true, false, or not be present)
            $isAllDay = filter_var($request->input('is_all_day', false), FILTER_VALIDATE_BOOLEAN);

            // Update event details
            $event->update([
                'title' => $request->title,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'location' => $request->location,
                'color' => $request->color,
                'is_all_day' => $isAllDay,
                'calendar_type' => $request->calendar_type,
                'private' => $request->boolean('private'),
            ]);

            // Handle guests update
            $guestEmails = json_decode($request->guests, true) ?? [];

            if (!empty($guestEmails)) {
                $guestIds = [];

                foreach ($guestEmails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $guest = EventGuest::firstOrCreate(['email' => $email]);
                        $guestIds[] = $guest->id;
                    }
                }

                $event->participants()->sync($guestIds);
            } else {
                $event->participants()->detach();
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Event updated successfully']);
            }

            return redirect()->route('home')->with('success', 'Event updated successfully!');
        } catch (\Exception $e) {
            \Log::error('Event update error: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Failed to update event: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();
        return redirect()->route('home')->with('success', 'Event deleted successfully!');
    }

    public function checkGuestConflicts(Request $request)
    {
        $request->validate([
            'guests' => 'required|json',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'event_id' => 'nullable|exists:events,id',
        ]);

        $guests = json_decode($request->guests, true);
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $eventId = $request->event_id;

        $conflictingGuests = [];

        foreach ($guests as $email) {
            $guest = EventGuest::where('email', $email)->first();
            if ($guest) {
                $query = $guest->events()
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($q) use ($startDate, $endDate) {
                                $q->where('start_date', '<=', $startDate)
                                    ->where('end_date', '>=', $endDate);
                            });
                    });

                if ($eventId) {
                    $query->where('events.id', '!=', $eventId);
                }

                $conflictingEvents = $query->get(['title', 'start_date as start', 'end_date as end']);

                if ($conflictingEvents->isNotEmpty()) {
                    $conflictingGuests[] = [
                        'email' => $email,
                        'events' => $conflictingEvents
                    ];
                }
            }
        }

        return response()->json([
            'conflicts' => $conflictingGuests
        ]);
    }

    public function storeGoogleEvent(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|min:1',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'location' => 'nullable|string',
                'color' => 'nullable|string',
                'guests' => 'nullable|string',
            ]);

            // Add validation for calendar_type if it's present
            if ($request->has('calendar_type')) {
                $request->validate([
                    'calendar_type' => 'required|in:institute,sector1,sector1_div1,sector2,sector2_div1,sector3,sector3_div1,sector4,sector4_div1',
                ]);
            }

            $data = [
                'title' => $request->title,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date ?: $request->start_date,
                'location' => $request->location,
                'color' => $request->color,
                'guests' => json_decode($request->guests, true) ?? [],
            ];

            // CREATE EVENT ON GOOGLE
            $googleEvent = $this->googleCalendarService->createEvent($data);

            // Save to local DB with google_event_id
            $event = Event::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'location' => $data['location'],
                'color' => $data['color'],
                'user_id' => auth()->id(),
                'is_all_day' => $request->is_all_day ?? false,
                'calendar_type' => 'division',
                'private' => $request->boolean('private'),
                'google_event_id' => $googleEvent->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Google Calendar event created and stored locally',
                'event' => $event
            ]);
        } catch (\Exception $e) {
            \Log::error('Google event creation error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateGoogleEvent(Request $request, $localEventId)
    {
        try {
            // Check if this is a Google event ID with google_ prefix
            if (strpos($localEventId, 'google_') === 0) {
                // Extract the actual Google event ID
                $googleEventId = substr($localEventId, 7); // Remove 'google_' prefix
                \Log::info('Removing google_ prefix from event ID', [
                    'original' => $localEventId,
                    'processed' => $googleEventId
                ]);
            } else {
                // Try to find the event in the database to get its Google event ID
                $event = Event::where('id', $localEventId)
                    ->orWhere('google_event_id', $localEventId)
                    ->first();

                if (!$event) {
                    return response()->json(['success' => false, 'error' => 'Event not found.'], 404);
                }

                $googleEventId = $event->google_event_id;
                if (!$googleEventId) {
                    return response()->json(['success' => false, 'error' => 'No Google event ID associated.']);
                }
            }

            $request->validate([
                'title' => 'required|string|min:1',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'location' => 'nullable|string',
                'color' => 'nullable|string',
                'guests' => 'nullable|string',
            ]);

            $data = [
                'title' => $request->title,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'location' => $request->location,
                'color' => $request->color,
                'guests' => json_decode($request->guests, true) ?? [],
            ];

            \Log::info('Updating Google event', [
                'eventId' => $googleEventId,
                'data' => $data
            ]);

            $updatedGoogleEvent = $this->googleCalendarService->updateEvent($googleEventId, $data);

            // Also update local event if it exists
            $localEvent = Event::where('google_event_id', $googleEventId)->first();
            if ($localEvent) {
                $localEvent->update([
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'location' => $data['location'],
                    'color' => $data['color'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Google Calendar event updated successfully',
                'event' => $updatedGoogleEvent
            ]);
        } catch (\Exception $e) {
            \Log::error('Google event update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function destroyGoogleEvent($localEventId)
    {
        try {
            $event = Event::findOrFail($localEventId);

            if (!$event->google_event_id) {
                return response()->json(['success' => false, 'error' => 'No Google event ID to delete.']);
            }

            $this->googleCalendarService->deleteEvent($event->google_event_id);

            $event->delete();

            return response()->json(['success' => true, 'message' => 'Event deleted successfully from Google and local DB']);
        } catch (\Exception $e) {
            \Log::error('Google event deletion error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    protected function getGoogleEvents()
    {
        try {
            if (!$this->googleCalendarService->isAuthenticated()) {
                \Log::info('User not authenticated with Google Calendar');
                return [];
            }

            // Debug user's Google Calendar ID
            $user = \Auth::user();
            \Log::info('User google_calendar_id:', [
                'user_id' => $user->id,
                'google_calendar_id' => $user->google_calendar_id
            ]);

            // Fetch events from Google Calendar
            $googleEvents = $this->googleCalendarService->getEvents([
                'timeMin' => date('c', strtotime('-30 days')),
                'timeMax' => date('c', strtotime('+60 days')),
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ]);

            \Log::info('Raw Google events fetched:', ['count' => count($googleEvents)]);

            // Format Google events to match the format expected by the calendar
            $formattedEvents = [];

            foreach ($googleEvents as $event) {
                // Extract start and end date information
                $start = null;
                $end = null;
                $isAllDay = false;

                if ($event->getStart()->getDate()) {
                    // All day event
                    $start = $event->getStart()->getDate();
                    $end = $event->getEnd()->getDate();
                    $isAllDay = true;
                } else {
                    // Timed event
                    $start = $event->getStart()->getDateTime();
                    $end = $event->getEnd()->getDateTime();
                }

                // Get attendees if any
                $attendees = [];
                if ($event->getAttendees()) {
                    foreach ($event->getAttendees() as $attendee) {
                        $attendees[] = $attendee->getEmail();
                    }
                }

                // Map Google color ID to hex color
                $colorMap = [
                    '1' => '#3b82f6', // Blue
                    '2' => '#22c55e', // Green
                    '3' => '#a855f7', // Purple
                    '4' => '#ec4899', // Pink
                    '5' => '#eab308', // Yellow
                    '6' => '#f97316', // Orange
                    '7' => '#14b8a6', // Teal
                    '8' => '#64748b', // Gray
                    '9' => '#6b7280', // Cool Gray
                    '10' => '#8b5cf6', // Indigo
                    '11' => '#ef4444', // Red
                ];

                $backgroundColor = '#3b82f6'; // Default blue
                if ($event->getColorId() && isset($colorMap[$event->getColorId()])) {
                    $backgroundColor = $colorMap[$event->getColorId()];
                }

                // Format the event data for the calendar
                $formattedEvents[] = [
                    'id' => 'google_' . $event->getId(),  // Prefix with 'google_' to identify as Google event
                    'title' => $event->getSummary(),
                    'start' => $start,
                    'end' => $end,
                    'allDay' => $isAllDay,
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => 'transparent',
                    'classNames' => ['gcal-event'],
                    'source' => 'google',
                    'extendedProps' => [
                        'description' => $event->getDescription(),
                        'location' => $event->getLocation(),
                        'guests' => $attendees,
                        'calendarType' => 'google',
                        'source' => 'google'
                    ]
                ];
            }

            \Log::info('Formatted Google events:', ['count' => count($formattedEvents)]);
            return $formattedEvents;

        } catch (\Exception $e) {
            \Log::error('Error fetching Google Calendar events: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
}
