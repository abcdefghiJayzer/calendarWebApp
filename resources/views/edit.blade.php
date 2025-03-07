@extends('layouts.app')

@section('content')
<div class="p-8">
    <h1 class="text-2xl font-bold mb-6">Edit Event</h1>

    <div class="bg-white rounded-lg p-6 w-full max-w-lg shadow-md">
        <form action="{{ route('update', $event->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- Event Color Selection -->
            <label for="color" class="block text-sm font-medium text-gray-700">Choose Event Color:</label>
            <div class="flex space-x-3 mt-2">
                @php
                $selectedColor = old('color', $event->color ?? '#3b82f6');
                @endphp

                @foreach([
                '#3b82f6' => 'bg-blue-500',
                '#ef4444' => 'bg-red-500',
                '#eab308' => 'bg-yellow-500',
                '#22c55e' => 'bg-green-500',
                '#000000' => 'bg-black'
                ] as $hex => $bg)
                <label class="cursor-pointer color-option rounded-full {{ $selectedColor == $hex ? 'ring-4 ring-offset-2 ring-blue-300' : '' }}" data-color="{{ $hex }}">
                    <input type="radio" name="color" value="{{ $hex }}" class="hidden" {{ $selectedColor == $hex ? 'checked' : '' }}>
                    <div class="w-8 h-8 rounded-full border-2 border-gray-300 {{ $bg }}"></div>
                </label>
                @endforeach
            </div>

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="title" id="title" value="{{ old('title', $event->title) }}" required
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="description"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">{{ old('description', $event->description) }}</textarea>
            </div>

            <!-- Start Date -->
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                <input type="datetime-local" name="start_date" id="start_date" value="{{ old('start_date', $event->start_date) }}" required
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>

            <!-- End Date -->
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                <input type="datetime-local" name="end_date" id="end_date" value="{{ old('end_date', $event->end_date) }}"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>

            <!-- Guests -->
            <div class="mb-4">
                <label class="block text-gray-700">Add guests</label>
                <div id="guest-container" class="flex flex-wrap gap-2 border p-2 rounded">
                    <input id="guest-input" type="email" class="outline-none border-none flex-grow p-1" placeholder="Type email and press Enter">

                </div>
                <input type="hidden" name="guests" id="guest-hidden" value='{{ json_encode($event->participants->pluck("email")) }}'>


            </div>

            <!-- Location -->
            <div>
                <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                <input type="text" name="location" id="location" value="{{ old('location', $event->location) }}"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>

            <!-- All Day Event -->
            <div class="flex items-center">
                <input type="hidden" name="is_all_day" value="0">
                <input type="checkbox" name="is_all_day" id="is_all_day" value="1" {{ old('is_all_day', $event->is_all_day) ? 'checked' : '' }}
                    class="text-green-600 focus:ring-green-500 rounded">
                <label for="is_all_day" class="ml-2 text-sm text-gray-700">All Day Event</label>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-2">
                <button type="submit"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Update Event
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Color Selection Script -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const colorOptions = document.querySelectorAll(".color-option input");

        colorOptions.forEach(option => {
            option.addEventListener("change", function() {
                // Remove highlight from all options
                document.querySelectorAll(".color-option").forEach(el => {
                    el.classList.remove("ring-4", "ring-offset-2", "ring-blue-300");
                });

                // Add highlight to selected option
                this.parentElement.classList.add("ring-4", "ring-offset-2", "ring-blue-300");
            });
        });
    });
</script>

<!-- Guest List Management Script -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const guestInput = document.getElementById("guest-input");
        const guestContainer = document.getElementById("guest-container");
        const guestHidden = document.getElementById("guest-hidden");

        let guests = JSON.parse(guestHidden.value) || [];
        // Retrieve existing guests

        function updateHiddenInput() {
            guestHidden.value = JSON.stringify(guests);
        }

        function createGuestTag(email) {
            const span = document.createElement("span");
            span.className = "px-2 py-1 bg-gray-200 rounded text-sm flex items-center";
            span.textContent = email;

            const removeBtn = document.createElement("button");
            removeBtn.innerHTML = "&times;";
            removeBtn.className = "ml-2 text-red-500 hover:text-red-700";
            removeBtn.onclick = function() {
                guests = guests.filter(g => g !== email);
                span.remove();
                updateHiddenInput();
            };

            span.appendChild(removeBtn);
            guestContainer.insertBefore(span, guestInput);
        }

        guests.forEach(createGuestTag); // Load existing guests

        guestInput.addEventListener("keydown", function(event) {
            if (event.key === "Enter" && guestInput.value.trim() !== "") {
                event.preventDefault();
                const email = guestInput.value.trim();
                if (!guests.includes(email)) {
                    guests.push(email);
                    createGuestTag(email);
                    updateHiddenInput();
                }
                guestInput.value = ""; // Clear input
            }
        });
    });
</script>
@endsection
