<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventGuest;
use App\Services\GoogleCalendarService;
use App\Models\User;

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
        try {
            $user = auth()->user();
            if (!$user) {
                \Log::error('User not authenticated when fetching events');
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            \Log::info('Fetching events for user', [
                'user_id' => $user->id,
                'division' => $user->division,
                'org_unit_id' => $user->organizational_unit_id
            ]);

            // Start with a base query
            $eventsQuery = Event::with(['participants', 'organizationalUnits']);

            // Show events that are either:
            // 1. Created by the user
            // 2. Events where the user's organizational unit is selected
            // 3. Events that are global (no organizational units)
            $eventsQuery->where(function($query) use ($user) {
                $query->where('user_id', $user->id)  // User's own events
                      ->orWhereHas('organizationalUnits', function($q) use ($user) {
                          // Check if user's organizational unit is in the event's organizational units
                          $q->where('organizational_units.id', $user->organizational_unit_id);
                      })
                      ->orWhereDoesntHave('organizationalUnits'); // Global events (no organizational units)
            });

            $events = $eventsQuery->select(
                'id',
                'title',
                'start_date as start',
                'end_date as end',
                'location',
                'color as backgroundColor',
                'description',
                'is_all_day as allDay',
                'private',
                'user_id'
            )->get();

            \Log::info('Found events', ['count' => $events->count()]);

            $events = $events->map(function ($event) use ($user) {
                try {
                    $data = $event->toArray();
                    if (isset($data['user_id'])) {
                        $data['user_id'] = (int)$data['user_id'];
                    }

                    // Get creator's role
                    $creator = User::find($data['user_id']);
                    $creatorRole = 'employee';
                    if ($creator) {
                        if ($creator->division === 'institute') {
                            $creatorRole = 'admin';
                        } else if ($creator->is_division_head) {
                            $creatorRole = 'division_head';
                        } else if ($creator->organizationalUnit && $creator->organizationalUnit->type === 'sector') {
                            $creatorRole = 'sectoral';
                        } else {
                            $creatorRole = 'employee';
                        }
                    }

                    // Get guests for this event
                    $guests = $event->participants->pluck('email')->toArray();
                    \Log::info('Event guests', [
                        'event_id' => $event->id,
                        'guests' => $guests
                    ]);

                    // Hide details if event is private and user is not the owner
                    if ($data['private'] && $data['user_id'] !== $user->id) {
                        $data['title'] = 'Private Event';
                        $data['backgroundColor'] = '#808080'; // Grey color for private events
                        $data['extendedProps'] = [
                            'description' => 'Private event - Details hidden',
                            'location' => null,
                            'guests' => [],
                            'private' => true,
                            'user_id' => $data['user_id'],
                            'creator_role' => $creatorRole
                        ];
                    } else {
                        $data['extendedProps'] = [
                            'description' => $data['description'],
                            'location' => $data['location'],
                            'guests' => $guests,
                            'private' => $data['private'],
                            'user_id' => (int)$data['user_id'],
                            'organizational_unit_ids' => $event->organizationalUnits->pluck('id')->toArray(),
                            'organizational_unit_names' => $event->organizationalUnits->pluck('name')->toArray(),
                            'is_global' => $event->organizationalUnits->isEmpty(),
                            'visible_to_organizational_units' => $event->organizationalUnits->pluck('id')->toArray(),
                            'creator_role' => $creatorRole
                        ];
                    }
                    return $data;
                } catch (\Exception $e) {
                    \Log::error('Error processing event', [
                        'event_id' => $event->id,
                        'error' => $e->getMessage()
                    ]);
                    return null;
                }
            })->filter();

            // Get Google Calendar events and combine with local events (if needed)
            $googleEvents = $this->getGoogleEvents();
            if (!empty($googleEvents)) {
                $events = $events->concat($googleEvents);
            }

            return response()->json($events);
        } catch (\Exception $e) {
            \Log::error('Error fetching events: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            return response()->json(['error' => 'Failed to fetch events: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $event = Event::with(['participants', 'organizationalUnits'])->find($id);

        if (!$event) {
            return response()->json(['error' => 'Event not found'], 404);
        }

        // Get creator's role
        $creator = User::find($event->user_id);
        $creatorRole = 'employee';
        if ($creator) {
            if ($creator->division === 'institute') {
                $creatorRole = 'admin';
            } else if ($creator->is_division_head) {
                $creatorRole = 'division_head';
            } else if ($creator->organizationalUnit && $creator->organizationalUnit->type === 'sector') {
                $creatorRole = 'sectoral';
            }
        }

        // Get organizational unit IDs and names
        $organizationalUnitIds = $event->organizationalUnits->pluck('id')->toArray();
        $organizationalUnitNames = $event->organizationalUnits->pluck('name')->toArray();

        // Determine if event is global (no organizational units)
        $isGlobal = empty($organizationalUnitIds);

        return response()->json([
            'id' => $event->id,
            'title' => $event->title,
            'start' => $event->start_date,
            'end' => $event->end_date,
            'allDay' => $event->is_all_day,
            'backgroundColor' => $event->color,
            'description' => $event->description,
            'location' => $event->location,
            'private' => $event->private,
            'user_id' => $event->user_id,
            'guests' => $event->participants->pluck('email'),
            'is_global' => $isGlobal,
            'organizational_unit_ids' => $organizationalUnitIds,
            'organizational_unit_names' => $organizationalUnitNames,
            'is_priority' => $event->is_priority,
            'extendedProps' => [
                'description' => $event->description,
                'location' => $event->location,
                'guests' => $event->participants->pluck('email'),
                'private' => $event->private,
                'user_id' => $event->user_id,
                'is_global' => $isGlobal,
                'organizational_unit_ids' => $organizationalUnitIds,
                'organizational_unit_names' => $organizationalUnitNames,
                'is_priority' => $event->is_priority,
                'creator_role' => $creatorRole
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
                'guests' => 'nullable|string', // JSON string of guest emails
                'is_all_day' => 'nullable|boolean',
                'is_global' => 'nullable|boolean',
                'is_priority' => 'boolean',
                'organizational_unit_ids' => 'nullable|array',
                'organizational_unit_ids.*' => 'nullable|numeric|exists:organizational_units,id',
                'force_create' => 'nullable|boolean', // New parameter to force creation despite overlaps
                'force_update' => 'nullable|boolean', // Alternative parameter name used in blade template
            ]);

            // Set end_date to start_date if not provided
            $endDate = $request->end_date ?: $request->start_date;

            // Get the selected organizational units from the dropdown
            $organizationalUnitIds = [];
            if ($request->has('organizational_unit_ids') && !empty($request->organizational_unit_ids)) {
                $organizationalUnitIds = $request->organizational_unit_ids;
            } else if (!$request->boolean('is_global')) {
                // If no units selected and not global, use user's organizational unit
                $user = auth()->user();
                if ($user->organizational_unit_id) {
                    $organizationalUnitIds = [$user->organizational_unit_id];
                }
            }

            // Check for overlapping priority events if this event is not a priority event
            if (!$request->boolean('is_priority')) {
                $overlappingEvents = Event::where('is_priority', true)
                    ->where(function ($query) use ($request) {
                        $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                            ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                            ->orWhere(function ($q) use ($request) {
                                $q->where('start_date', '<=', $request->start_date)
                                    ->where('end_date', '>=', $request->end_date);
                            });
                    });

                // Check for overlaps in the selected organizational units
                if (!empty($organizationalUnitIds)) {
                    $overlappingEvents->whereHas('organizationalUnits', function ($q) use ($organizationalUnitIds) {
                        $q->whereIn('organizational_units.id', $organizationalUnitIds);
                    });
                } else {
                    // For global events, check all priority events
                    $overlappingEvents->whereDoesntHave('organizationalUnits');
                }

                $overlappingEvents = $overlappingEvents->get();

                if ($overlappingEvents->isNotEmpty() && !$request->boolean('force_create') && !$request->boolean('force_update')) {
                    return response()->json([
                        'success' => false,
                        'error' => 'This time slot overlaps with priority events. Please choose a different time or contact the event organizers.',
                        'overlapping_events' => $overlappingEvents->map(function ($event) {
                            return [
                                'title' => $event->title,
                                'start_date' => $event->start_date,
                                'end_date' => $event->end_date,
                                'organizational_units' => $event->organizationalUnits->pluck('name')
                            ];
                        })->toArray(),
                        'can_force_create' => true
                    ], 422);
                }
            }

            $user = auth()->user();

            $color = '#616161'; // Default color for division employee
            
            switch($user->role) {
                case 'admin':
                    $color = '#33b679'; // Admin color
                    break;
                case 'sector_head':
                    $color = '#039be5'; // Sector head color
                    break;
                case 'division_head':
                    $color = '#e8b4bc'; // Division head color
                    break;
                default:
                    $color = '#616161'; // Division employee color
            }

            $event = Event::create([
                'title' => $request->title,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $endDate,
                'location' => $request->location,
                'user_id' => auth()->id(),
                'is_all_day' => $request->is_all_day ?? false,
                'status' => $request->status ?? 'pending',
                'color' => $color,
                'private' => $request->boolean('private'),
                'is_priority' => $request->boolean('is_priority'),
            ]);

            // Associate the event with the selected organizational units
            if (!empty($organizationalUnitIds)) {
                $event->organizationalUnits()->sync($organizationalUnitIds);
            }

            // Handle guests
            $guestEmails = json_decode($request->guests, true) ?? [];

            foreach ($guestEmails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $guest = EventGuest::firstOrCreate(['email' => $email]);
                    $event->participants()->attach($guest->id);
                }
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Event created successfully',
                    'event' => $event->load('organizationalUnits')
                ]);
            }

            return redirect()->route('home')->with('success', 'Event created successfully!');
        } catch (\Exception $e) {
            \Log::error('Event creation error: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to create event: ' . $e->getMessage());
        }
    }

    public function edit(string $id)
    {
        $event = Event::findOrFail($id);

        // Admin can edit any event
        if (auth()->user()->division !== 'institute' && !auth()->user()->canCreateEventsIn($event->visibility)) {
            return redirect()->route('home')->with('error', 'You do not have permission to edit this event.');
        }

        return view('edit', compact('event'));
    }

    public function update(Request $request, string $id)
    {
        try {
            $event = Event::findOrFail($id);
            
            // Check ownership
            if (auth()->user()->division !== 'institute' && auth()->id() !== $event->user_id) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to edit this event.'
                ], 403);
            }

            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'guests' => 'nullable|json',
                'location' => 'nullable|string|max:255',
                'is_all_day' => 'boolean',
                'is_global' => 'nullable|boolean',
                'is_priority' => 'boolean',
                'organizational_unit_ids' => 'nullable|array',
                'organizational_unit_ids.*' => 'nullable|numeric|exists:organizational_units,id',
                'force_update' => 'nullable|boolean',
            ]);

            // Properly handle the is_all_day checkbox
            $isAllDay = filter_var($request->input('is_all_day', false), FILTER_VALIDATE_BOOLEAN);

            // Set end_date to start_date if not provided
            $endDate = $request->end_date ?: $request->start_date;

            // Determine organizational units based on settings
            $organizationalUnitIds = [];

            // Important: Load existing organizational units from the pivot table first
            $existingOrgUnitIds = $event->organizationalUnits->pluck('id')->toArray();

            // Only change organizational units if they were actually included in the request
            if ($request->has('is_global') || $request->has('organizational_unit_ids')) {
                if ($request->boolean('is_global')) {
                    // For global events, don't associate with any specific unit
                    $organizationalUnitIds = [];
                } else {
                    // If not global and organizational unit ids are provided, use them
                    if ($request->has('organizational_unit_ids')) {
                        $organizationalUnitIds = $request->organizational_unit_ids ?: [];
                    } else {
                        // If no organizational units are provided, keep existing ones
                        $organizationalUnitIds = $existingOrgUnitIds;
                    }
                }
            } else {
                // If neither is_global nor organizational_unit_ids were provided, keep existing settings
                $organizationalUnitIds = $existingOrgUnitIds;
            }

            // Check for overlapping priority events ONLY if not a priority event AND not forcing update
            if (!$request->boolean('is_priority') && !$request->boolean('force_update')) {
                $overlappingEvents = Event::where('is_priority', true)
                    ->where('id', '!=', $id) // Exclude current event
                    ->where(function ($query) use ($request) {
                        $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                            ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                            ->orWhere(function ($q) use ($request) {
                                $q->where('start_date', '<=', $request->start_date)
                                    ->where('end_date', '>=', $request->end_date);
                            });
                    });

                // Check for overlaps in the selected organizational units
                if (!empty($organizationalUnitIds)) {
                    $overlappingEvents->whereHas('organizationalUnits', function ($q) use ($organizationalUnitIds) {
                        $q->whereIn('organizational_units.id', $organizationalUnitIds);
                    });
                } else {
                    // For global events, check all priority events
                    $overlappingEvents->whereDoesntHave('organizationalUnits');
                }

                $overlappingEvents = $overlappingEvents->get();

                if ($overlappingEvents->isNotEmpty()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'This time slot overlaps with priority events. Please choose a different time or contact the event organizers.',
                        'overlapping_events' => $overlappingEvents->map(function ($event) {
                            return [
                                'title' => $event->title,
                                'start_date' => $event->start_date,
                                'end_date' => $event->end_date,
                                'organizational_units' => $event->organizationalUnits->pluck('name')
                            ];
                        })->toArray(),
                        'can_force_update' => true
                    ], 422);
                }
            }

            // Update event details
            $user = auth()->user();
            $color = '#616161'; // Default color for division employee
            
            switch($user->role) {
                case 'admin':
                    $color = '#33b679'; // Admin color
                    break;
                case 'sector_head':
                    $color = '#039be5'; // Sector head color
                    break;
                case 'division_head':
                    $color = '#e8b4bc'; // Division head color
                    break;
                default:
                    $color = '#616161'; // Division employee color
            }

            $event->update([
                'title' => $request->title,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $endDate,
                'location' => $request->location,
                'is_all_day' => $isAllDay,
                'private' => $request->boolean('private'),
                'is_priority' => $request->boolean('is_priority'),
                'color' => $color,
            ]);

            // Handle organizational units
            $event->organizationalUnits()->sync($organizationalUnitIds);

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

            // If this event is connected to Google Calendar, update the Google event as well
            $googleUpdated = false;
            if ($event->google_event_id && $this->googleCalendarService->isAuthenticated()) {
                try {
                    // Prepare event data for Google Calendar
                    $googleEventData = [
                        'title' => $event->title,
                        'description' => $event->description,
                        'start_date' => $event->start_date,
                        'end_date' => $event->end_date ?: $event->start_date, // Use start_date as fallback
                        'location' => $event->location,
                        'color' => $event->color,
                        'guests' => $event->participants->pluck('email')->toArray(),
                        'is_all_day' => $event->is_all_day
                    ];

                    // Use user's stored Google credentials from database
                    $user = auth()->user();

                    // Load and use the Google tokens from the database regardless of session state
                    $this->googleCalendarService->useUserTokens($user);

                    // Update the Google Calendar event
                    $this->googleCalendarService->updateEvent($event->google_event_id, $googleEventData);
                    \Log::info('Google Calendar event updated with local changes', [
                        'event_id' => $event->id,
                        'google_event_id' => $event->google_event_id
                    ]);
                    $googleUpdated = true;
                } catch (\Exception $e) {
                    \Log::error('Failed to update Google Calendar event', [
                        'event_id' => $event->id,
                        'google_event_id' => $event->google_event_id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue with the response even if Google update fails
                    // We don't want to prevent updating the local event if Google sync fails
                }
            }

            if ($request->ajax() || $request->wantsJson()) {
                $response = [
                    'success' => true,
                    'message' => 'Event updated successfully'
                ];

                if ($event->google_event_id) {
                    $response['google_synced'] = $googleUpdated;
                    if (!$googleUpdated) {
                        $response['google_sync_message'] = 'Note: The event was updated locally, but we couldn\'t sync the changes to Google Calendar.';
                    }
                }

                return response()->json($response);
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

        // Ensure only the event creator can delete it
        if (auth()->id() !== $event->user_id) {
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to delete this event.'], 403);
            }
            return redirect()->route('home')->with('error', 'You do not have permission to delete this event.');
        }

        $event->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Event deleted successfully']);
        }
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
                'is_global' => 'nullable|boolean',
                'organizational_unit_ids' => 'nullable|array',
                'organizational_unit_ids.*' => 'nullable|numeric|exists:organizational_units,id',
            ]);

            // Determine organizational units based on settings
            $organizationalUnitIds = [];

            if ($request->boolean('is_global')) {
                // For global events, don't associate with any specific unit
                $organizationalUnitIds = [];
            } else {
                $user = auth()->user();

                if ($user->division !== 'institute') {
                    if ($user->organizational_unit_id) {
                        $organizationalUnitIds = [$user->organizational_unit_id];
                    }
                } else if ($request->has('organizational_unit_ids') && !empty($request->organizational_unit_ids)) {
                    // Only institute users can select organizational units
                    $organizationalUnitIds = $request->organizational_unit_ids;
                }
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
                'visibility' => 'institute',
                'private' => $request->boolean('private'),
                'google_event_id' => $googleEvent->id
            ]);

            // Handle organizational units
            if (!empty($organizationalUnitIds)) {
                // Associate with selected or automatically determined organizational units
                $event->organizationalUnits()->sync($organizationalUnitIds);
            }

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
                'is_global' => 'nullable|boolean',
                'organizational_unit_ids' => 'nullable|array',
                'organizational_unit_ids.*' => 'nullable|numeric|exists:organizational_units,id',
            ]);

            // Determine organizational units based on settings
            $organizationalUnitIds = [];

            if ($request->boolean('is_global')) {
                // For global events, don't associate with any specific unit
                $organizationalUnitIds = [];
            } else {
                $user = auth()->user();

                if ($user->division !== 'institute') {
                    if ($user->organizational_unit_id) {
                        $organizationalUnitIds = [$user->organizational_unit_id];
                    }
                } else if ($request->has('organizational_unit_ids') && !empty($request->organizational_unit_ids)) {
                    // Only institute users can select organizational units
                    $organizationalUnitIds = $request->organizational_unit_ids;
                }
            }

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
                    'visibility' => 'institute',
                ]);

                // Handle organizational units
                if (!empty($organizationalUnitIds)) {
                    // Associate with selected or automatically determined organizational units
                    $localEvent->organizationalUnits()->sync($organizationalUnitIds);
                }
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

    /**
     * Sync all local events to Google Calendar
     */
    public function syncAllToGoogle(Request $request)
    {
        try {
            // Check if user is authenticated with Google Calendar
            if (!$this->googleCalendarService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must connect your Google account first',
                    'redirect' => route('google.auth')
                ]);
            }

            // Add a lock to prevent multiple syncs running at the same time
            $lockKey = 'google_calendar_sync_' . auth()->id();
            if (!\Cache::add($lockKey, true, 300)) { // 5 minute lock
                \Log::info('Sync already in progress, skipping', [
                    'user_id' => auth()->id(),
                    'lock_key' => $lockKey
                ]);
                // Return empty response to prevent any alerts
                return response()->json([
                    'success' => true,
                    'message' => null,
                    'results' => [
                        'success' => [],
                        'skipped' => [],
                        'failed' => []
                    ]
                ]);
            }

            try {
                $user = auth()->user();
                $isAdmin = $user->division === 'institute';
                $isDivisionHead = $user->is_division_head;

                // Build query based on user's permissions
                $eventsQuery = Event::with('participants');

                if ($isAdmin) {
                    // Admin sees institute and all sector-level events
                    $eventsQuery->where(function ($query) {
                        $query->where('visibility', 'institute')
                              ->orWhereIn('visibility', ['sector1', 'sector2', 'sector3', 'sector4']);
                    });
                } else if ($isDivisionHead) {
                    $userSector = explode('_', $user->division)[0];
                    $eventsQuery->where(function($query) use ($user, $userSector) {
                        $query->where('visibility', 'institute')
                              ->orWhere('visibility', $userSector)
                              ->orWhere('visibility', $user->division);
                    });
                } else {
                    $eventsQuery->where(function($query) use ($user) {
                        $query->where('visibility', 'institute')
                              ->orWhere('visibility', $user->division);
                    });
                }

                // Get all events that need syncing
                $events = $eventsQuery->get();

                \Log::info('Starting sync process with events', [
                    'user_id' => $user->id,
                    'total_events' => $events->count()
                ]);

                $results = [
                    'success' => [],
                    'skipped' => [],
                    'failed' => []
                ];

                // Get all existing Google Calendar events
                $existingGoogleEvents = $this->googleCalendarService->getEvents([
                    'timeMin' => date('c', strtotime('-30 days')),
                    'timeMax' => date('c', strtotime('+60 days')),
                    'singleEvents' => true,
                    'orderBy' => 'startTime',
                ]);

                // Create a map of existing Google events by ID for quick lookup
                $existingEventsById = [];
                foreach ($existingGoogleEvents as $googleEvent) {
                    $existingEventsById[$googleEvent->getId()] = $googleEvent;
                }

                // Track processed events to prevent duplicates
                $processedEventIds = [];

                // Process each event exactly once
                foreach ($events as $event) {
                    try {
                        // Skip if we've already processed this event
                        if (in_array($event->id, $processedEventIds)) {
                            \Log::info('Skipping already processed event', [
                                'event_id' => $event->id,
                                'title' => $event->title
                            ]);
                            continue;
                        }

                        // Start a database transaction for this event
                        \DB::beginTransaction();

                        try {
                            // Case 1: Event has no Google ID - Create new event
                            if (!$event->google_event_id) {
                                $data = [
                                    'title' => $event->title,
                                    'description' => $event->description,
                                    'start_date' => $event->start_date,
                                    'end_date' => $event->end_date ?: $event->start_date,
                                    'location' => $event->location,
                                    'color' => $event->color,
                                    'guests' => $event->participants->pluck('email')->toArray(),
                                    'is_all_day' => $event->is_all_day
                                ];

                                $googleEvent = $this->googleCalendarService->createEvent($data);

                                // Update the event with the new Google ID
                                $event->google_event_id = $googleEvent->getId();
                                $event->save();

                                $results['success'][] = [
                                    'id' => $event->id,
                                    'title' => $event->title,
                                    'google_event_id' => $googleEvent->getId(),
                                    'reason' => 'New event created in Google Calendar'
                                ];

                                \Log::info('New event synced to Google Calendar', [
                                    'event_id' => $event->id,
                                    'title' => $event->title,
                                    'google_event_id' => $googleEvent->getId()
                                ]);
                            }
                            // Case 2: Event has Google ID - Check if it exists
                            else {
                                if (!isset($existingEventsById[$event->google_event_id])) {
                                    // Event was deleted from Google Calendar - Recreate it
                                    $data = [
                                        'title' => $event->title,
                                        'description' => $event->description,
                                        'start_date' => $event->start_date,
                                        'end_date' => $event->end_date ?: $event->start_date,
                                        'location' => $event->location,
                                        'color' => $event->color,
                                        'guests' => $event->participants->pluck('email')->toArray(),
                                        'is_all_day' => $event->is_all_day
                                    ];

                                    $googleEvent = $this->googleCalendarService->createEvent($data);

                                    // Update the event with the new Google ID
                                    $event->google_event_id = $googleEvent->getId();
                                    $event->save();

                                    $results['success'][] = [
                                        'id' => $event->id,
                                        'title' => $event->title,
                                        'google_event_id' => $googleEvent->getId(),
                                        'reason' => 'Recreated deleted event in Google Calendar'
                                    ];

                                    \Log::info('Recreated deleted event in Google Calendar', [
                                        'event_id' => $event->id,
                                        'title' => $event->title,
                                        'google_event_id' => $googleEvent->getId()
                                    ]);
                                } else {
                                    $results['skipped'][] = [
                                        'id' => $event->id,
                                        'title' => $event->title,
                                        'reason' => 'Event already exists in Google Calendar'
                                    ];
                                }
                            }

                            // Mark this event as processed
                            $processedEventIds[] = $event->id;

                            // Commit the transaction
                            \DB::commit();

                        } catch (\Exception $e) {
                            // Rollback the transaction on error
                            \DB::rollBack();
                            throw $e;
                        }

                    } catch (\Exception $e) {
                        \Log::error('Failed to sync event', [
                            'event_id' => $event->id,
                            'error' => $e->getMessage()
                        ]);

                        $results['failed'][] = [
                            'id' => $event->id,
                            'title' => $event->title,
                            'error' => $e->getMessage()
                        ];
                    }
                }

                // Count results
                $successCount = count($results['success']);
                $skippedCount = count($results['skipped']);
                $failedCount = count($results['failed']);

                // Only show message if there are actual changes
                $message = null;
                if ($successCount > 0) {
                    $message = "Successfully synced {$successCount} event" . ($successCount > 1 ? 's' : '') . " to Google Calendar.";
                }

                // Check if this is an automatic sync (from login)
                $isAutomaticSync = !$request->ajax() && !$request->wantsJson();
                if ($isAutomaticSync) {
                    return $message;
                }

                // For manual sync (AJAX/JSON requests), return JSON response
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'results' => $results
                ]);

            } finally {
                // Always release the lock when done
                \Cache::forget($lockKey);
            }

        } catch (\Exception $e) {
            \Log::error('Error in sync process: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Check if this is an automatic sync
            $isAutomaticSync = !$request->ajax() && !$request->wantsJson();
            if ($isAutomaticSync) {
                return null;
            }

            return response()->json([
                'success' => true,
                'message' => null,
                'results' => [
                    'success' => [],
                    'skipped' => [],
                    'failed' => []
                ]
            ]);
        }
    }

    /**
     * Sync a single local event to Google Calendar
     */
    public function syncToGoogle(Request $request, $id)
    {
        try {
            // Find the event with its participants
            $event = Event::with('participants')->findOrFail($id);

            // Check ownership
            if ($event->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only sync events you created'
                ], 403);
            }

            $user = auth()->user();

            // Check if user has Google tokens in database
            if (empty($user->google_access_token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must connect your Google account first',
                    'redirect' => route('google.auth')
                ]);
            }

            // Use the stored tokens from the database
            $this->googleCalendarService->useUserTokens($user);

            // Check if already synced and the Google event still exists
            if ($event->google_event_id) {
                try {
                    // Try to retrieve the event from Google to verify it exists
                    $googleEvent = $this->googleCalendarService->getEvent($event->google_event_id);

                    if ($googleEvent) {
                        // Event exists in Google Calendar, no need to recreate
                        return response()->json([
                            'success' => true,
                            'message' => 'This event is already synced with Google Calendar',
                            'alreadySynced' => true,
                            'google_event_id' => $event->google_event_id
                        ]);
                    } else {
                        // Google event doesn't exist anymore, clear the ID so we can recreate it
                        \Log::info('Google event not found, will recreate', [
                            'event_id' => $event->id,
                            'google_event_id' => $event->google_event_id
                        ]);
                        $event->google_event_id = null;
                    }
                } catch (\Exception $e) {
                    // Error checking Google event - assume it's gone and we need to recreate
                    \Log::warning('Error checking if Google event exists, will recreate', [
                        'event_id' => $event->id,
                        'google_event_id' => $event->google_event_id,
                        'error' => $e->getMessage()
                    ]);
                    $event->google_event_id = null;
                }
            }

            // Prepare event data for Google Calendar with proper formatting
            $data = [
                'title' => $event->title,
                'description' => $event->description,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date ?: $event->start_date, // Use start_date as fallback
                'location' => $event->location,
                'color' => $event->color,
                'guests' => $event->participants->pluck('email')->toArray(),
                'is_all_day' => $event->is_all_day
            ];

            // Validate dates before proceeding
            $startTimestamp = strtotime($data['start_date']);
            $endTimestamp = strtotime($data['end_date']);

            if (!$startTimestamp || !$endTimestamp) {
                throw new \Exception("Invalid date format: Start: {$data['start_date']}, End: {$data['end_date']}");
            }

            // Create the event in Google Calendar
            $googleEvent = $this->googleCalendarService->createEvent($data);

            // Update the local event with the Google event ID
            $event->google_event_id = $googleEvent->id;
            $event->save();

            \Log::info('Event synced to Google Calendar', [
                'event_id' => $event->id,
                'google_event_id' => $googleEvent->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event successfully synced to Google Calendar',
                'google_event_id' => $googleEvent->id
            ]);
        } catch (\Exception $e) {
            \Log::error('Error syncing event to Google Calendar: ' . $e->getMessage(), [
                'event_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = $e->getMessage();
            // Check for specific Google API errors and provide better messages
            if (strpos($errorMessage, 'Invalid') !== false && strpos($errorMessage, 'date') !== false) {
                $errorMessage = 'Failed to sync: The event has invalid date values. Please check the start and end dates.';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage
            ], 500);
        }
    }

    protected function getGoogleEvents()
    {
        try {
            $user = auth()->user();

            // Check if the user has Google tokens stored in the database
            if (empty($user->google_access_token)) {
                \Log::info('User has no Google access token stored');
                return [];
            }

            // Use the stored tokens from the database
            $this->googleCalendarService->useUserTokens($user);

            // Debug user's Google Calendar ID
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

            // Get list of Google event IDs that are already linked to local events
            // These should be excluded to prevent duplication
            $syncedGoogleEventIds = Event::whereNotNull('google_event_id')
                ->pluck('google_event_id')
                ->toArray();

            \Log::info('Found synced Google event IDs in local database:', [
                'count' => count($syncedGoogleEventIds)
            ]);

            // Format Google events to match the format expected by the calendar
            $formattedEvents = [];

            foreach ($googleEvents as $event) {
                // Skip this Google event if it's already linked to a local event
                if (in_array($event->getId(), $syncedGoogleEventIds)) {
                    \Log::debug('Skipping Google event that is already synced locally', [
                        'google_event_id' => $event->getId()
                    ]);
                    continue;
                }

                // Skip events that were created by our app (they will be shown from local DB)
                if ($event->getCreator() && $event->getCreator()->getEmail() === $user->email) {
                    \Log::debug('Skipping Google event created by our app', [
                        'google_event_id' => $event->getId(),
                        'creator' => $event->getCreator()->getEmail()
                    ]);
                    continue;
                }

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
                        'source' => 'google',
                        'google_event_id' => $event->getId()
                    ]
                ];
            }

            \Log::info('Formatted Google events (after removing duplicates):', ['count' => count($formattedEvents)]);
            return $formattedEvents;

        } catch (\Exception $e) {
            \Log::error('Error fetching Google Calendar events: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
}
