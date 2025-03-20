<!-- Modal backdrop -->
<div id="add-event-modal" class="fixed inset-0 z-[999] overflow-y-auto hidden">
    <!-- Backdrop -->
    <div onclick="closeModal()" class="fixed inset-0 bg-black/60"></div>

    <!-- Modal panel -->
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="relative bg-white rounded-lg p-6 w-full max-w-lg shadow-xl transform transition-all">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Create Event</h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="add-event-form" class="space-y-4">
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

                <div class="mb-4">
                    <label class="block text-gray-700">Add guests</label>
                    <div id="guest-container" class="flex flex-wrap gap-2 border p-2 rounded">
                        <input id="guest-input" type="email" class="outline-none border-none flex-grow p-1" placeholder="Type email and press Enter">
                    </div>
                    <input type="hidden" name="guests" id="guest-hidden" value="[]">
                </div>

                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                    <input type="text" name="location" id="location"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>

                <div class="flex items-center">
                    <input type="hidden" name="is_all_day" value="0">
                    <input type="checkbox" name="is_all_day" id="is_all_day" value="1"
                        class="text-green-600 focus:ring-green-500 rounded">
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
</div>

<script>
    let guests = []; // Move guests array to global scope

    function openModal() {
        document.getElementById('add-event-modal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('add-event-modal').classList.add('hidden');
        // Reset form and guests when closing
        document.getElementById('add-event-form').reset();
        guests = [];
        document.getElementById('guest-hidden').value = '[]';
        // Remove all guest tags
        const guestContainer = document.getElementById('guest-container');
        const guestInput = document.getElementById('guest-input');
        Array.from(guestContainer.children).forEach(child => {
            if (child !== guestInput) child.remove();
        });
    }

    function updateHiddenInput() {
        document.getElementById('guest-hidden').value = JSON.stringify(guests);
    }

    function createGuestTag(email) {
        const guestContainer = document.getElementById('guest-container');
        const guestInput = document.getElementById('guest-input');

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

    document.addEventListener("DOMContentLoaded", function() {
        const colorOptions = document.querySelectorAll(".color-option input");
        const guestInput = document.getElementById("guest-input");

        colorOptions.forEach(option => {
            option.addEventListener("change", function() {
                document.querySelectorAll(".color-option").forEach(el => {
                    el.classList.remove("ring-4", "ring-offset-2", "ring-blue-300");
                });
                this.parentElement.classList.add("ring-4", "ring-offset-2", "ring-blue-300");
            });
        });

        guestInput.addEventListener("keydown", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                const email = guestInput.value.trim();
                if (email !== "") {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (emailRegex.test(email)) {
                        if (!guests.includes(email)) {
                            guests.push(email);
                            createGuestTag(email);
                            updateHiddenInput();
                        }
                        guestInput.value = "";
                        guestInput.classList.remove('border-red-500');
                    } else {
                        guestInput.classList.add('border-red-500');
                        alert('Please enter a valid email address');
                    }
                }
            }
        });
    });

    document.getElementById('add-event-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        // Handle any pending guest input
        const emailInput = document.getElementById('guest-input');
        const email = emailInput.value.trim();
        if (email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(email) && !guests.includes(email)) {
                guests.push(email);
                createGuestTag(email);
                updateHiddenInput();
                emailInput.value = "";
            }
        }

        const formData = new FormData(this);

        try {
            const response = await fetch('{{ route('store') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                closeModal();
                window.location.href = '/OJT/calendarWebApp/';
            } else {
                const errorData = await response.json();
                alert(errorData.message || 'Failed to create event');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while creating the event');
        }
    });

    // Replace existing click outside handler with this one
    document.addEventListener('mousedown', function(event) {
        const modal = document.getElementById('add-event-modal');
        const modalContent = modal.querySelector('.relative.bg-white');

        if (modal && !modal.classList.contains('hidden') && !modalContent.contains(event.target)) {
            closeModal();
        }
    }, true);
</script>
