@extends('layouts.app')

@section('content')
<div class="p-8">
    <h1 class="text-2xl font-bold mb-6">Create Event</h1>

    <div class="bg-white rounded-lg p-6 w-full max-w-lg shadow-md">
        <form action="{{ route('store') }}" method="POST" class="space-y-4">
            @csrf

            <label for="color" class="block text-sm font-medium text-gray-700">Choose Event Color:</label>
            <div class="flex space-x-3 mt-2">
                @php
                $selectedColor = old('color', '#3b82f6'); // Default to blue
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

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="description"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">{{ old('description') }}</textarea>
            </div>

            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                <input type="datetime-local" name="start_date" id="start_date" value="{{ old('start_date') }}" required
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>

            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                <input type="datetime-local" name="end_date" id="end_date" value="{{ old('end_date') }}"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700">Add guests</label>
                <div id="guest-container" class="flex flex-wrap gap-2 border p-2 rounded">
                    <input id="guest-input" type="email" class="outline-none border-none flex-grow p-1" placeholder="Type email and press Enter">
                </div>

                <input type="hidden" name="guests" id="guest-hidden" value="{{ old('guests', '[]') }}">
            </div>

            <div>
                <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                <input type="text" name="location" id="location" value="{{ old('location') }}"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>

            <div class="flex items-center">
                <input type="hidden" name="is_all_day" value="0">
                <input type="checkbox" name="is_all_day" id="is_all_day" value="1" {{ old('is_all_day') ? 'checked' : '' }}
                    class="text-green-600 focus:ring-green-500 rounded">
                <label for="is_all_day" class="ml-2 text-sm text-gray-700">All Day Event</label>
            </div>

            <div class="flex items-center mt-2">
                <input type="hidden" name="private" value="0">
                <input type="checkbox" name="private" id="private" value="1" {{ old('private') ? 'checked' : '' }}
                    class="text-green-600 focus:ring-green-500 rounded">
                <label for="private" class="ml-2 text-sm text-gray-700">Private Event</label>
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

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const colorOptions = document.querySelectorAll(".color-option input");

        colorOptions.forEach(option => {
            option.addEventListener("change", function() {
                document.querySelectorAll(".color-option").forEach(el => {
                    el.classList.remove("ring-4", "ring-offset-2", "ring-blue-300");
                });
                this.parentElement.classList.add("ring-4", "ring-offset-2", "ring-blue-300");
            });
        });

        const guestInput = document.getElementById("guest-input");
        const guestContainer = document.getElementById("guest-container");
        const guestHidden = document.getElementById("guest-hidden");
        const form = document.querySelector("form");

        let guests = JSON.parse(guestHidden.value || "[]");

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

        guests.forEach(createGuestTag);

        guestInput.addEventListener("keydown", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                addGuest();
            }
        });

        function addGuest() {
            const email = guestInput.value.trim();
            if (email && !guests.includes(email)) {
                guests.push(email);
                createGuestTag(email);
                updateHiddenInput();
                guestInput.value = "";
            }
        }

        form.addEventListener("submit", function(event) {
            addGuest(); // Handle any pending guest input
        });
    });
</script>
@endsection
