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
        // Automatic sync removed - events will only sync when manually triggered
    }

    /**
     * Handle the Event "updated" event.
     */
    public function updated(Event $event): void
    {
        // Automatic sync removed - events will only sync when manually triggered
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
}