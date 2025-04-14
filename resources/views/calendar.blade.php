@extends('layouts.app')

@section('content')
<div class="transition-all duration-300 ease-in-out w-full" id="calendar-container">
    <div id="calendar"
        data-events-url="{{ route('getEvents') }}"
        data-api-key="{{ config('services.google.calendar.api_key') }}"
        data-calendar-id="{{ config('services.google.calendar.calendar_id') }}"
        data-is-authenticated="{{ $isGoogleAuthenticated ? 'true' : 'false' }}"
        class="bg-white rounded-lg shadow p-4 h-[96vh]">
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Calendar initialization:', {
        apiKey: document.getElementById('calendar').getAttribute('data-api-key'),
        calendarId: document.getElementById('calendar').getAttribute('data-calendar-id'),
        eventsUrl: document.getElementById('calendar').getAttribute('data-events-url'),
        isAuthenticated: document.getElementById('calendar').getAttribute('data-is-authenticated')
    });

    // Get the action flag from session (single flag for both operations)
    const googleCalendarAction = @json(session('google_calendar_action', false));

    if (googleCalendarAction === 'enable_and_refresh') {
        console.log('Google account connected, enabling checkbox and refreshing events');

        // We need to wait for both calendar and sidebar to be fully loaded
        const isReady = () => {
            return window.calendar && document.querySelector('.calendar-filter[value="google"]');
        };

        const initializeAfterConnect = () => {
            if (isReady()) {
                // 1. First clean up any existing Google Calendar sources
                cleanupGoogleEvents();

                // 2. Update the filter checkbox (only after we've confirmed calendar is loaded)
                const googleCheckbox = document.querySelector('.calendar-filter[value="google"]');
                if (googleCheckbox) {
                    googleCheckbox.checked = true;

                    // Update saved filters to include Google Calendar
                    let savedFilters = localStorage.getItem('calendarFilters')
                        ? JSON.parse(localStorage.getItem('calendarFilters'))
                        : ['institute', 'sectoral', 'division'];

                    if (!savedFilters.includes('google')) {
                        savedFilters.push('google');
                        localStorage.setItem('calendarFilters', JSON.stringify(savedFilters));
                        console.log('Updated saved filters to include Google Calendar');
                    }
                }

                // 3. After a short delay to ensure filter changes are processed, add the Google Calendar source
                setTimeout(() => {
                    addGoogleCalendarSource();
                }, 300);
            } else {
                setTimeout(initializeAfterConnect, 300);
            }
        };

        // Start checking if calendar is ready
        initializeAfterConnect();
    }

    // Helper function to clean up Google events
    function cleanupGoogleEvents() {
        if (!window.calendar) return;

        console.log('Cleaning up existing Google Calendar sources and events');

        // 1. Remove all Google Calendar sources
        window.calendar.getEventSources().forEach(source => {
            if (source.url && source.url.includes('google')) {
                console.log('Removing Google event source:', source);
                source.remove();
            }
        });

        // 2. Remove any individual Google events
        const googleEvents = window.calendar.getEvents().filter(event =>
            event.id.startsWith('google_') ||
            (event.extendedProps && event.extendedProps.source === 'google')
        );

        if (googleEvents.length > 0) {
            console.log(`Removing ${googleEvents.length} lingering Google events`);
            googleEvents.forEach(event => event.remove());
        }
    }

    // Helper function to add Google Calendar source
    function addGoogleCalendarSource() {
        if (!window.calendar) return;

        const apiKey = document.getElementById('calendar').getAttribute('data-api-key');
        const calendarId = document.getElementById('calendar').getAttribute('data-calendar-id');
        const isAuthenticated = document.getElementById('calendar').getAttribute('data-is-authenticated') === 'true';
        const connectedAccount = sessionStorage.getItem('connected_google_account') || '';

        if (apiKey && calendarId && isAuthenticated) {
            console.log('Adding fresh Google Calendar source');

            window.calendar.addEventSource({
                id: 'google-calendar-source', // Add a unique id for easier removal
                googleCalendarId: calendarId,
                googleCalendarApiKey: apiKey,
                className: 'gcal-event',
                color: '#0288d1',
                textColor: 'white',
                cache: false,
                extraParams: {
                    account: connectedAccount,
                    _: new Date().getTime() // Cache-busting
                },
                eventDataTransform: function(eventData) {
                    if (!eventData.extendedProps) {
                        eventData.extendedProps = {};
                    }
                    eventData.extendedProps.source = 'google';
                    return eventData;
                },
            });

            // Force a refetch of all events
            window.calendar.refetchEvents();
        }
    }
});
</script>
@endsection
