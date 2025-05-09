<div id="add-event-modal" class="fixed inset-y-0 right-0 z-[999] w-120 transform translate-x-full transition-transform duration-300 ease-in-out">
    <div class="h-full bg-gray-50 shadow-xl shadow-black/10">
        <div class="p-8 h-full overflow-y-auto shadow-[-8px_0_15px_-3px_rgba(0,0,0,0.1)]">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Create Event</h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="add-event-form" action="{{ route('events.store') }}" method="POST" class="space-y-5">
                @csrf

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Choose Event Color:</label>
                    <div class="flex space-x-3">
                        @foreach([
                        '#3b82f6' => 'bg-blue-500',
                        '#ef4444' => 'bg-red-500',
                        '#eab308' => 'bg-yellow-500',
                        '#22c55e' => 'bg-green-500',
                        '#000000' => 'bg-black'
                        ] as $hex => $bg)
                        <label class="cursor-pointer color-option rounded-full group" data-color="{{ $hex }}">
                            <input type="radio" name="color" value="{{ $hex }}" {{ $hex === '#3b82f6' ? 'checked' : '' }} class="hidden">
                            <div class="w-8 h-8 rounded-full border-2 border-gray-300 {{ $bg }} transition-all duration-200 group-hover:scale-110 {{ $hex === '#3b82f6' ? 'ring-4 ring-offset-2 ring-blue-300' : '' }}"></div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="calendar_type" class="block text-sm font-medium text-gray-700">Calendar Type</label>
                    <div class="relative">
                        <select name="calendar_type" id="calendar_type" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            @if(auth()->user()->division === 'institute')
                                <option value="institute">Institute-wide Calendar (For All Divisions)</option>
                                <optgroup label="Sector Calendars">
                                    <option value="sector1">Sector 1 (All Sector 1 Divisions)</option>
                                    <option value="sector2">Sector 2 (All Sector 2 Divisions)</option>
                                    <option value="sector3">Sector 3 (All Sector 3 Divisions)</option>
                                    <option value="sector4">Sector 4 (All Sector 4 Divisions)</option>
                                </optgroup>
                                <optgroup label="Division-specific Calendars">
                                    <option value="sector1_div1">Sector 1 - Division 1 Only</option>
                                    <option value="sector2_div1">Sector 2 - Division 1 Only</option>
                                    <option value="sector3_div1">Sector 3 - Division 1 Only</option>
                                    <option value="sector4_div1">Sector 4 - Division 1 Only</option>
                                </optgroup>
                            @elseif(auth()->user()->is_division_head)
                                @php
                                    $userDivision = auth()->user()->division;
                                    $userSector = explode('_', $userDivision)[0];
                                @endphp
                                <option value="{{ $userSector }}">{{ ucfirst($userSector) }} - All Divisions</option>
                                <option value="{{ $userDivision }}">{{ ucfirst(str_replace('_', ' - ', $userDivision)) }} Only</option>
                            @else
                                <option value="{{ auth()->user()->division }}">
                                    {{ ucfirst(str_replace('_', ' - ', auth()->user()->division)) }} Calendar
                                </option>
                            @endif
                        </select>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        <strong>Calendar Types:</strong><br>
                        • Institute-wide: Visible to everyone<br>
                        • Sector: Events for an entire sector with multiple divisions<br>
                        • Division-specific: Events that only affect one division
                    </p>
                </div>

                <div class="space-y-2">
                    <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" name="title" id="title" required 
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <div class="space-y-2">
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="3"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="datetime-local" name="start_date" id="start_date" required
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div class="space-y-2">
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="datetime-local" name="end_date" id="end_date"
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Add guests</label>
                    <div id="guest-container" class="flex flex-wrap gap-2 p-2 bg-white border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 transition-colors">
                        <input id="guest-input" type="email" class="outline-none border-none flex-grow p-1" placeholder="Type email and press Enter">
                    </div>
                    <input type="hidden" name="guests" id="guest-hidden" value="[]">
                </div>

                <div class="space-y-2">
                    <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                    <input type="text" name="location" id="location"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <div class="flex items-center space-x-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_all_day" value="0">
                        <input type="checkbox" name="is_all_day" id="is_all_day" value="1" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700">All Day Event</span>
                    </label>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="private" value="0">
                        <input type="checkbox" name="private" id="private" value="1" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700">Private Event</span>
                    </label>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Create Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let guests = [];

    function openModal(startDate = null, endDate = null) {
        const modal = document.getElementById('add-event-modal');
        modal.classList.remove('translate-x-full');
        document.getElementById('calendar-container').classList.add('mr-120');

        if (startDate) {
            const startLocal = new Date(startDate.getTime() - startDate.getTimezoneOffset() * 60000);
            document.getElementById('start_date').value = startLocal.toISOString().slice(0, 16);

            if (endDate) {
                const endLocal = new Date(endDate.getTime() - endDate.getTimezoneOffset() * 60000);
                document.getElementById('end_date').value = endLocal.toISOString().slice(0, 16);
            } else {
                document.getElementById('end_date').value = startLocal.toISOString().slice(0, 16);
            }
        }

        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.id = 'add-backdrop';
        backdrop.className = 'fixed inset-0 bg-black/2 z-[998] transition-opacity duration-300';
        backdrop.onclick = closeModal;
        document.body.appendChild(backdrop);

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        const modal = document.getElementById('add-event-modal');
        modal.classList.add('translate-x-full');
        document.getElementById('calendar-container').classList.remove('mr-120');

        // Remove backdrop
        const backdrop = document.getElementById('add-backdrop');
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
        span.className = "px-2 py-1 bg-gray-100 rounded-full text-sm flex items-center group";
        span.textContent = email;

        const removeBtn = document.createElement("button");
        removeBtn.innerHTML = "&times;";
        removeBtn.className = "ml-2 text-gray-400 hover:text-red-500 transition-colors";
        removeBtn.onclick = function() {
            guests = guests.filter(g => g !== email);
            span.remove();
            updateHiddenInput();
        };

        span.appendChild(removeBtn);
        guestContainer.insertBefore(span, guestInput);
    }

    document.addEventListener("DOMContentLoaded", function() {
        // Initialize first color option as selected
        const firstColorOption = document.querySelector(".color-option input");
        if (firstColorOption) {
            firstColorOption.checked = true;
            firstColorOption.parentElement.classList.add("ring-4", "ring-offset-2", "ring-blue-300");
        }

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

        // Initialize calendar type based on user's division
        const userDivision = "{{ auth()->user()->division }}";
        const calendarTypeSelect = document.getElementById('calendar_type');

        if (calendarTypeSelect) {
            calendarTypeSelect.value = userDivision;
        }
    });

    document.getElementById('add-event-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        try {
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
                    await Swal.fire({
                        icon: 'error',
                        title: 'Invalid Email',
                        text: 'Please enter a valid email address',
                        confirmButtonColor: '#22c55e'
                    });
                    return;
                }
            }

            const formData = new FormData(this);

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
            const response = await fetch(this.getAttribute('action'), {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            let responseData;
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                responseData = await response.json();
            } else {
                // Handle non-JSON response
                responseData = {
                    success: response.ok,
                    message: response.ok ? 'Event created successfully!' : 'Failed to create event'
                };
            }

            if (response.ok) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: responseData.message || 'Event created successfully!',
                    confirmButtonColor: '#22c55e'
                });

                closeModal();
                window.location.reload();
            } else {
                throw new Error(responseData.message || 'Failed to create event');
            }

        } catch (error) {
            console.error('Error:', error);
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'An error occurred while creating the event',
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
