@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-6 shadow rounded-lg">
    @if(!$event->private || auth()->id() === $event->user_id)
        <h1 class="text-2xl font-bold text-gray-800">{{ $event->title }}</h1>
        <p class="text-gray-600 mt-2">{{ $event->description }}</p>

        <div class="mt-4">
            <p><strong>Start Date:</strong> {{ \Carbon\Carbon::parse($event->start_date)->format('F d, Y - h:i A') }}</p>
            <p><strong>End Date:</strong> {{ \Carbon\Carbon::parse($event->end_date)->format('F d, Y - h:i A') }}</p>
            @if($event->location)
                <p><strong>Location:</strong> {{ $event->location }}</p>
            @endif
            @if ($event->participants && $event->participants->isNotEmpty())
                <h3 class="font-semibold mt-2">Guests:</h3>
                <ol class="list-disc ml-6">
                    @foreach ($event->participants as $guest)
                        <li>{{ $guest->email }}</li>
                    @endforeach
                </ol>
            @endif
        </div>
    @else
        <h1 class="text-2xl font-bold text-gray-800">Private Event</h1>
        <p class="text-gray-600 mt-2">This is a private event. Only date and time are visible.</p>
        <div class="mt-4">
            <p><strong>Start Date:</strong> {{ \Carbon\Carbon::parse($event->start_date)->format('F d, Y - h:i A') }}</p>
            <p><strong>End Date:</strong> {{ \Carbon\Carbon::parse($event->end_date)->format('F d, Y - h:i A') }}</p>
        </div>
    @endif

    <div class="mt-4 flex space-x-2">
        <a href="{{ url('/') }}" class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Back to Calendar
        </a>

        @if(!$event->private || auth()->id() === $event->user_id)
            <button onclick="editEvent({{ $event->id }})" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                Edit Event
            </button>
        @endif

        <form action="{{ route('destroy', $event->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                Delete Event
            </button>
        </form>
    </div>
</div>

<script>
    function deleteEvent(eventId) {
        if (!confirm("Are you sure you want to delete this event?")) return;

        fetch(`/events/${eventId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.message === 'Event deleted successfully') {
                    alert('Event deleted successfully');
                    window.location.href = '/'; // Redirect to the calendar view
                } else {
                    alert('Failed to delete the event');
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function editEvent(eventId) {
        fetch(`/OJT/calendarWebApp/events/${eventId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(event => {
                if (event) {
                    openEditModal(event);
                } else {
                    throw new Error('Event data is empty');
                }
            })
            .catch(error => {
                console.error('Error fetching event data:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load event data',
                    confirmButtonColor: '#22c55e'
                });
            });
    }
</script>
@endsection
