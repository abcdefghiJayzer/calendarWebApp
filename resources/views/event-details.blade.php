@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-6 shadow rounded-lg">
    <h1 class="text-2xl font-bold text-gray-800">{{ $event->title }}</h1>
    <p class="text-gray-600 mt-2">{{ $event->description }}</p>

    <div class="mt-4">
        <p><strong>Start Date:</strong> {{ \Carbon\Carbon::parse($event->start_date)->format('F d, Y - h:i A') }}</p>
        <p><strong>End Date:</strong> {{ \Carbon\Carbon::parse($event->end_date)->format('F d, Y - h:i A') }}</p>
        <p><strong>Location:</strong> {{ $event->location }}</p>
        <p><strong>Status:</strong> {{ ucfirst($event->status) }}</p>
    </div>

    <a href="{{ url('/') }}" class="mt-4 inline-block bg-blue-500 text-white px-4 py-2 rounded">Back to Calendar</a>
</div>
@endsection
