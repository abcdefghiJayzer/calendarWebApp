@extends('layouts.app')

@section('content')
<div class="p-8">
    <h1 class="text-2xl font-bold mb-6">Create Event</h1>

    <div class="bg-white rounded-lg p-6 w-full max-w-lg shadow-md">
        <form action="{{ route('store') }}" method="POST" class="space-y-4">
            @csrf
            <label for="color" class="block text-sm font-medium text-gray-700">Choose Event Color:</label>
            <div class="flex space-x-3 mt-2">
                <label class="cursor-pointer">
                    <input type="radio" name="color" value="#3b82f6" class="hidden" required>
                    <div class="w-8 h-8 rounded-full border-2 border-gray-300 bg-blue-500"></div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="color" value="#ef4444" class="hidden">
                    <div class="w-8 h-8 rounded-full border-2 border-gray-300 bg-red-500"></div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="color" value="#eab308" class="hidden">
                    <div class="w-8 h-8 rounded-full border-2 border-gray-300 bg-yellow-500"></div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="color" value="#22c55e" class="hidden">
                    <div class="w-8 h-8 rounded-full border-2 border-gray-300 bg-green-500"></div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="color" value="#000000" class="hidden">
                    <div class="w-8 h-8 rounded-full border-2 border-gray-300 bg-black"></div>
                </label>
            </div>


            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="title" id="title" required
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="description"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500"></textarea>
            </div>

            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                <input type="datetime-local" name="start_date" id="start_date" required
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>

            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                <input type="datetime-local" name="end_date" id="end_date"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>

            <div>
                <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                <input type="text" name="location" id="location"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>

            <div class="flex items-center">
                <input type="hidden" name="is_all_day" value="0">
                <input type="checkbox" name="is_all_day" id="is_all_day" value="1" class="text-green-600 focus:ring-green-500 rounded">
                <label for="is_all_day" class="ml-2 text-sm text-gray-700">All Day Event</label>

            </div>




            <div class="flex justify-end space-x-2">
                <button type="submit"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Create Event
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
