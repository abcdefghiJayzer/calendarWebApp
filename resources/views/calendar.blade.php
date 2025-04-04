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
});
</script>
@endsection
