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
        $events = Event::with('participants')
            ->select(
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

                // Hide details if event is private and user is not the owner
                if ($event->private && $event->user_id !== auth()->id()) {
                    $data['title'] = 'Private Event';
                    $data['backgroundColor'] = '#808080'; // Grey color for private events
                    $data['extendedProps'] = [
                        'description' => 'Private event - Details hidden',
                        'location' => null,
                        'guests' => [],
                        'calendarType' => $event->calendar_type,
                        'private' => true,
                        'user_id' => $event->user_id
                    ];
                } else {
                    $data['extendedProps'] = [
                        'description' => $event->description,
                        'location' => $event->location,
                        'guests' => $event->participants->pluck('email'),
                        'calendarType' => $event->calendar_type,
                        'private' => $event->private,
                        'user_id' => $event->user_id
                    ];
                }

                return $data;
            });
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
            'extendedProps' => [
                'description' => $event->description,
                'location' => $event->location,
                'guests' => $event->participants->pluck('email'),
                'private' => $event->private,
                'user_id' => $event->user_id,
                'calendar_type' => $event->calendar_type
            ]
        ]);
    }

    public function create()
    {
        return view('add');
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
                'calendar_type' => 'required|in:institute,sectoral,division',
            ]);

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
                'calendar_type' => $request->calendar_type ?? 'division',
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
        return view('edit', compact('event'));
    }

    public function update(Request $request, string $id)
    {
        try {
            $event = Event::findOrFail($id);

            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'guests' => 'nullable|json',
                'location' => 'nullable|string|max:255',
                'color' => 'required|string|max:20',
                'is_all_day' => 'boolean',
                'calendar_type' => 'required|in:institute,sectoral,division',
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
                'color' => 'required|string',
                'guests' => 'nullable|string',
            ]);

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
            $event = Event::findOrFail($localEventId);

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

            if (!$event->google_event_id) {
                return response()->json(['success' => false, 'error' => 'No Google event ID associated.']);
            }

            $updatedGoogleEvent = $this->googleCalendarService->updateEvent($event->google_event_id, $data);

            // Also update local event
            $event->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Google Calendar event updated successfully',
                'event' => $updatedGoogleEvent
            ]);
        } catch (\Exception $e) {
            \Log::error('Google event update error: ' . $e->getMessage());
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
}
