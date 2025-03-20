@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-6 shadow rounded-lg">
    <h1 class="text-2xl font-bold text-gray-800">{{ $event->title }}</h1>
    <p class="text-gray-600 mt-2">{{ $event->description }}</p>

    <div class="mt-4">
        <p><strong>Start Date:</strong> {{ \Carbon\Carbon::parse($event->start_date)->format('F d, Y - h:i A') }}</p>
        <p><strong>End Date:</strong> {{ \Carbon\Carbon::parse($event->end_date)->format('F d, Y - h:i A') }}</p>
        <p><strong>Guests:</strong></p>
        @if ($event->participants && $event->participants->isNotEmpty())
        <h3 class="font-semibold">Guests:</h3>
        <ol class="list-disc ml-6">
            @foreach ($event->participants as $guest)
            <li>{{ $guest->email }}</li>
            @endforeach
        </ol>
        @endif

        <p class="mt-4"><strong>Location:</strong> {{ $event->location }}</p>
        <p><strong>Status:</strong> {{ ucfirst($event->status) }}</p>
        <p><strong>All Day Event:</strong> {{ $event->is_all_day ? 'Yes' : 'No' }}</p>
    </div>

    <div class="mt-4 flex space-x-2">
        <a href="{{ url('/') }}" class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Back to Calendar
        </a>

        <a href="{{ route('edit', $event->id) }}" class="inline-block bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
            Edit Event
        </a>
    </div>
</div>
@endsection
