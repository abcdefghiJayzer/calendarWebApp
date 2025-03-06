@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-6 shadow rounded-lg">
    <h1 class="text-2xl font-bold text-gray-800">Edit Event</h1>

    <form action="{{ route('update', $event->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-gray-700">Title</label>
            <input type="text" name="title" value="{{ $event->title }}" required
                class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700">Description</label>
            <textarea name="description" class="w-full border rounded p-2">{{ $event->description }}</textarea>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700">Start Date</label>
            <input type="datetime-local" name="start_date" value="{{ \Carbon\Carbon::parse($event->start_date)->format('Y-m-d\TH:i') }}" required
                class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700">End Date</label>
            <input type="datetime-local" name="end_date" value="{{ \Carbon\Carbon::parse($event->end_date)->format('Y-m-d\TH:i') }}" required
                class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700">Location</label>
            <input type="text" name="location" value="{{ $event->location }}" class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700">Status</label>
            <select name="status" class="w-full border rounded p-2">
                <option value="pending" {{ $event->status == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="confirmed" {{ $event->status == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="cancelled" {{ $event->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Update Event</button>
        <button type="button" onclick="window.history.back()" class="ml-2 text-gray-600">Cancel</button>

    </form>
</div>
@endsection
