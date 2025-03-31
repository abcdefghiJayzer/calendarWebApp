<!-- Right Sidebar -->
<div id="add-event-modal" class="fixed inset-y-0 right-0 z-[999] w-120 transform translate-x-full transition-transform duration-300 ease-in-out">
    <!-- Content -->
    <div class="h-full bg-white shadow-xl shadow-black/10">
        <div class="p-10 h-full overflow-y-auto shadow-[-8px_0_15px_-3px_rgba(0,0,0,0.1)]">
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
                <div class="mb-4">
                    <label for="calendar_type" class="block text-sm font-medium text-gray-700">Calendar Type</label>
                    <select name="calendar_type" id="calendar_type" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="">Select Calendar</option>
                        <option value="institute">Institute Level</option>
                        <option value="sectoral">Sectoral</option>
                        <option value="division">Division</option>
                    </select>
                </div>

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
                    <div class="border p-2 rounded">
                        <input type="text" name="title" id="title" required
                            class="outline-none border-none w-full">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <div class="border p-2 rounded">
                        <textarea name="description" id="description"
                            class="outline-none border-none w-full"></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <div class="border p-2 rounded">
                            <input type="datetime-local" name="start_date" id="start_date" required
                                class="outline-none border-none w-full">
                        </div>
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                        <div class="border p-2 rounded">
                            <input type="datetime-local" name="end_date" id="end_date"
                                class="outline-none border-none w-full">
                        </div>
                    </div>
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
                    <div class="border p-2 rounded">
                        <input type="text" name="location" id="location"
                            class="outline-none border-none w-full">
                    </div>
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
    let guests = [];

    function openModal() {
        document.getElementById('add-event-modal').classList.remove('translate-x-full');
        document.getElementById('calendar-container').classList.add('mr-120');
        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.id = 'modal-backdrop';
        backdrop.className = 'fixed inset-0 bg-black/20 z-[998] transition-opacity duration-300';
        backdrop.onclick = closeModal;
        document.body.appendChild(backdrop);
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        setTimeout(() => window.calendar?.updateSize(), 300); // After transition
    }

    function closeModal() {
        document.getElementById('add-event-modal').classList.add('translate-x-full');
        document.getElementById('calendar-container').classList.remove('mr-120');
        // Remove backdrop
        const backdrop = document.getElementById('modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        // Restore body scroll
        document.body.style.overflow = '';
        document.getElementById('add-event-form').reset();
        guests = [];
        document.getElementById('guest-hidden').value = '[]';
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
            if (emailRegex.test(email)) {
                if (!guests.includes(email)) {
                    guests.push(email);
                    createGuestTag(email);
                    updateHiddenInput();
                }
                emailInput.value = "";
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Email',
                    text: 'Please enter a valid email address',
                    confirmButtonColor: '#22c55e'
                });
                return;
            }
        }

        const formData = new FormData(this);

        try {
            // Check for guest scheduling conflicts
            if (guests.length > 0) {
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value || startDate;

                const checkData = new URLSearchParams();
                checkData.append('guests', JSON.stringify(guests));
                checkData.append('start_date', startDate);
                checkData.append('end_date', endDate);
                checkData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                const checkResponse = await fetch('/OJT/calendarWebApp/check-conflicts', {
                    method: 'POST',
                    body: checkData,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const conflictResult = await checkResponse.json();

                if (conflictResult.conflicts && conflictResult.conflicts.length > 0) {
                    let conflictHtml = 'The following guests have scheduling conflicts:<br><br>';

                    conflictResult.conflicts.forEach(conflict => {
                        conflictHtml += `<strong>${conflict.email}</strong> has conflicts with:<br>`;

                        conflict.events.forEach(event => {
                            const startDate = new Date(event.start).toLocaleString();
                            const endDate = new Date(event.end).toLocaleString();
                            conflictHtml += `- <b>${event.title}</b><br>`;
                            conflictHtml += `&nbsp;&nbsp;From: ${startDate}<br>`;
                            conflictHtml += `&nbsp;&nbsp;To: ${endDate}<br>`;
                        });

                        conflictHtml += '<br>';
                    });

                    const result = await Swal.fire({
                        title: 'Schedule Conflict Detected',
                        html: conflictHtml,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#22c55e',
                        cancelButtonColor: '#ef4444',
                        confirmButtonText: 'Add anyway',
                        cancelButtonText: 'Cancel'
                    });

                    if (!result.isConfirmed) {
                        return; // Just return without closing the modal
                    }
                }
            }

            // Proceed with event creation
            const response = await fetch('{{ route('store') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                closeModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Event added successfully!',
                    confirmButtonColor: '#22c55e'
                }).then(() => {
                    window.location.href = '/OJT/calendarWebApp/';
                });
            } else {
                const errorData = await response.json();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorData.message || 'Failed to create event',
                    confirmButtonColor: '#22c55e'
                });x
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while creating the event',
                confirmButtonColor: '#22c55e'
            });
        }
    });

    // Click outside handler
    document.addEventListener('mousedown', function(event) {
        if (document.querySelector('.swal2-container')) {
            return;
        }

        const modal = document.getElementById('add-event-modal');
        const modalContent = modal.querySelector('.h-full.bg-white');

        if (modal && !modal.classList.contains('translate-x-full') && !modalContent.contains(event.target)) {
            closeModal();
        }
    }, true);
</script>
