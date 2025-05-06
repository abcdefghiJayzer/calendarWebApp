<?php

namespace App\Observers;

use App\Models\Event;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Log;

class EventObserver
{
    protected $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Handle the Event "created" event.
     */
    public function created(Event $event): void
    {
        $this->syncWithGoogleCalendar($event);
    }

    /**
     * Handle the Event "updated" event.
     */
    public function updated(Event $event): void
    {
        $this->syncWithGoogleCalendar($event);
    }

    /**
     * Handle the Event "deleted" event.
     */
    public function deleted(Event $event): void
    {
        // Only delete from Google if we have a Google event ID
        if ($event->google_event_id && $this->googleCalendarService->isAuthenticated()) {
            try {
                $this->googleCalendarService->deleteEvent($event->google_event_id);
                Log::info('Event automatically deleted from Google Calendar', [
                    'event_id' => $event->id,
                    'google_event_id' => $event->google_event_id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to automatically delete event from Google Calendar', [
                    'event_id' => $event->id,
                    'google_event_id' => $event->google_event_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Sync an event with Google Calendar
     */
    private function syncWithGoogleCalendar(Event $event): void
    {
        // Only proceed if the user is authenticated with Google
        if (!$this->googleCalendarService->isAuthenticated()) {
            return;
        }

        // Load the event with participants
        $event->load('participants');

        try {
            if ($event->google_event_id) {
                // Update existing Google event
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

                $this->googleCalendarService->updateEvent($event->google_event_id, $eventData);
                Log::info('Event automatically updated in Google Calendar', [
                    'event_id' => $event->id,
                    'google_event_id' => $event->google_event_id
                ]);
            } else {
                // Create new Google event
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

                $googleEvent = $this->googleCalendarService->createEvent($eventData);
                
                // Save the Google event ID to the local event
                $event->google_event_id = $googleEvent->id;
                $event->saveQuietly(); // Use saveQuietly to avoid triggering another update event
                
                Log::info('Event automatically created in Google Calendar', [
                    'event_id' => $event->id,
                    'google_event_id' => $googleEvent->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to automatically sync event with Google Calendar', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}