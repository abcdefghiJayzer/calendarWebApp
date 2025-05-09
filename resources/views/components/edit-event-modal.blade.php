<div id="edit-event-modal" class="fixed inset-y-0 right-0 z-[999] w-120 transform translate-x-full transition-transform duration-300 ease-in-out">
    <div class="h-full bg-gray-50 shadow-xl shadow-black/10">
        <div class="p-8 h-full overflow-y-auto shadow-[-8px_0_15px_-3px_rgba(0,0,0,0.1)]">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Edit Event</h2>
                <button onclick="window.closeEditModal()" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="edit-event-form" class="space-y-5">
                @csrf
                <input type="hidden" id="edit-event-id" name="id">

                <div class="space-y-2">
                    <label for="edit-calendar_type" class="block text-sm font-medium text-gray-700">Calendar Type</label>
                    <div class="relative">
                        @if(auth()->user()->division === 'institute')
                            <select name="calendar_type" id="edit-calendar_type" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
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
                            </select>
                        @elseif(auth()->user()->is_division_head)
                            @php
                                $userDivision = auth()->user()->division;
                                $userSector = explode('_', $userDivision)[0];
                            @endphp
                            <select name="calendar_type" id="edit-calendar_type" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="{{ $userSector }}">{{ ucfirst($userSector) }} - All Divisions</option>
                                <option value="{{ $userDivision }}">{{ ucfirst(str_replace('_', ' - ', $userDivision)) }} Only</option>
                            </select>
                        @else
                            <select name="calendar_type" id="edit-calendar_type" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="{{ auth()->user()->division }}">
                                    {{ ucfirst(str_replace('_', ' - ', auth()->user()->division)) }} Calendar
                                </option>
                            </select>
                            <p class="text-xs text-gray-500 italic mt-1">
                                Regular users can only create/edit events in their assigned division calendar.
                            </p>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        <strong>Calendar Types:</strong><br>
                        • Institute-wide: Visible to everyone<br>
                        • Sector: Events for an entire sector with multiple divisions<br>
                        • Division-specific: Events that only affect one division
                    </p>
                </div>

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
                            <label class="cursor-pointer edit-color-option rounded-full group" data-color="{{ $hex }}">
                                <input type="radio" name="color" value="{{ $hex }}" class="hidden">
                                <div class="w-8 h-8 rounded-full border-2 border-gray-300 {{ $bg }} transition-all duration-200 group-hover:scale-110"></div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="edit-title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" name="title" id="edit-title" required
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <div class="space-y-2">
                    <label for="edit-description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="edit-description" rows="3"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="edit-start-date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="datetime-local" name="start_date" id="edit-start-date" required
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>
                    <div class="space-y-2">
                        <label for="edit-end-date" class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="datetime-local" name="end_date" id="edit-end-date" required
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Edit guests</label>
                    <div id="edit-guest-container" class="flex flex-wrap gap-2 p-2 bg-white border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 transition-colors">
                        <input id="edit-guest-input" type="email" class="outline-none border-none flex-grow p-1"
                            placeholder="Type email and press Enter">
                    </div>
                    <input type="hidden" name="guests" id="edit-guest-hidden" value="[]">
                </div>

                <div class="space-y-2">
                    <label for="edit-location" class="block text-sm font-medium text-gray-700">Location</label>
                    <input type="text" name="location" id="edit-location"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <div class="flex items-center space-x-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_all_day" value="0">
                        <input type="checkbox" name="is_all_day" id="edit-is-all-day" value="1" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700">All Day Event</span>
                    </label>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="private" value="0">
                        <input type="checkbox" name="private" id="edit-private" value="1" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700">Private Event</span>
                    </label>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" id="cancel-edit-btn"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button type="button" id="sync-to-google-btn" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2" />
                        </svg>
                        Sync to Google
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Update Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Define editGuests variable in global scope
let editGuests = [];

// IMPORTANT: Define openEditModal immediately in the global scope
// This ensures it's available as soon as the script is loaded
window.openEditModal = function(event) {
    console.log('openEditModal function called!', event);

    // Safeguard against missing event object
    if (!event) {
        console.error('No event data provided to openEditModal');
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No event data available for editing',
            confirmButtonColor: '#22c55e'
        });
        return;
    }

    // Error check - if we got an error object instead of an event
    if (event.error) {
        console.error('Error data provided to openEditModal:', event.error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: event.error || 'Failed to load event data',
            confirmButtonColor: '#22c55e'
        });
        return;
    }

    // Only block editing for Google events
    if (event.isGoogleEvent) {
        Swal.fire({
            icon: 'warning',
            title: 'Not Editable',
            text: 'Google Calendar events cannot be edited from this app.',
            confirmButtonColor: '#22c55e'
        });
        return;
    }

    // Check for required properties
    if (!event.start || !event.id) {
        console.error('Invalid event data provided to openEditModal:', event);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Invalid event data - missing required properties',
            confirmButtonColor: '#22c55e'
        });
        return;
    }

    // Verify current user has permission to edit this event
    const currentUserId = {{ auth()->id() }};
    const eventCreatorId = parseInt(event.extendedProps.user_id) || null;

    // Log for debugging
    console.log('Edit check - Current user:', currentUserId, 'Creator:', eventCreatorId);
    console.log('Full event data:', event);

    // Allow edit only if user is the creator (even for admins)
    const isAdmin = "{{ auth()->user()->division }}" === "institute";
    if (eventCreatorId && currentUserId != eventCreatorId) {
        Swal.fire({
            icon: 'error',
            title: 'Permission Denied',
            text: 'Only the creator of this event can edit it',
            confirmButtonColor: '#22c55e'
        });
        return;
    }

    // Additional permission check for division access (keep this for division-level permissions)
    console.log('Opening edit modal with event:', event);

    const modal = document.getElementById('edit-event-modal');
    modal.classList.remove('translate-x-full');
    document.getElementById('calendar-container').classList.add('mr-120');

    // Add backdrop
    const backdrop = document.createElement('div');
    backdrop.id = 'edit-backdrop';
    backdrop.className = 'fixed inset-0 bg-black/3 z-[998] transition-opacity duration-300';
    backdrop.onclick = window.closeEditModal;
    document.body.appendChild(backdrop);

    document.body.style.overflow = 'hidden';

    // Store whether this is a Google event - ensure we capture this explicitly
    window.currentEditingGoogleEvent = event.isGoogleEvent || false;
    console.log('Is Google event:', window.currentEditingGoogleEvent);

    // Handle both API and FullCalendar event formats
    const eventData = {
        id: event.id || '',
        title: event.title || 'Untitled Event',
        start: event.start,
        end: event.end,
        allDay: event.allDay || false,
        backgroundColor: event.backgroundColor || '#3b82f6',
        extendedProps: event.extendedProps || {}
    };

    // Set form fields
    document.getElementById('edit-event-id').value = eventData.id;
    document.getElementById('edit-title').value = eventData.title;

    // Handle description - might be directly on event or in extendedProps
    const description = eventData.extendedProps.description || event.description || '';
    document.getElementById('edit-description').value = description;

    // Handle location - might be directly on event or in extendedProps
    const location = eventData.extendedProps.location || event.location || '';
    document.getElementById('edit-location').value = location;

    // All-day checkbox
    document.getElementById('edit-is-all-day').checked = eventData.allDay;

    // Private checkbox
    const isPrivate = eventData.extendedProps.private || event.private || false;
    document.getElementById('edit-private').checked = isPrivate;

    // Set calendar type
    const calendarType = eventData.extendedProps.calendar_type ||
                         eventData.extendedProps.calendarType ||
                         event.calendar_type ||
                         'division';

    // Handle both admin and non-admin cases for calendar type
    if (document.getElementById('edit-calendar_type')) {
        document.getElementById('edit-calendar_type').value = calendarType;
    }

    // Fix date handling with extra validation
    try {
        const startDate = new Date(eventData.start);
        const endDate = eventData.end ? new Date(eventData.end) : new Date(startDate);

        // Validate dates before proceeding
        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
            throw new Error('Invalid date value');
        }

        // Add timezone offset to compensate for local timezone
        const startLocal = new Date(startDate.getTime() - startDate.getTimezoneOffset() * 60000);
        const endLocal = new Date(endDate.getTime() - endDate.getTimezoneOffset() * 60000);

        document.getElementById('edit-start-date').value = startLocal.toISOString().slice(0, 16);
        document.getElementById('edit-end-date').value = endLocal.toISOString().slice(0, 16);
    } catch (err) {
        console.error('Error processing event dates:', err);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Invalid event date format',
            confirmButtonColor: '#22c55e'
        });
        window.closeEditModal();
        return;
    }

    // Set color
    const colorOption = document.querySelector(`.edit-color-option[data-color="${eventData.backgroundColor}"]`);
    if (colorOption) {
        const colorInput = colorOption.querySelector('input');
        colorInput.checked = true;
        document.querySelectorAll(".edit-color-option").forEach(el => {
            el.classList.remove("ring-4", "ring-offset-2", "ring-blue-300");
        });
        colorOption.classList.add("ring-4", "ring-offset-2", "ring-blue-300");
    }

    // Set guests
    editGuests = eventData.extendedProps.guests || event.guests || [];
    document.getElementById('edit-guest-hidden').value = JSON.stringify(editGuests);
    const guestContainer = document.getElementById('edit-guest-container');
    const guestInput = document.getElementById('edit-guest-input');

    Array.from(guestContainer.children).forEach(child => {
        if (child !== guestInput) child.remove();
    });

    editGuests.forEach(email => createEditGuestTag(email));

    // Show/hide Sync to Google button based on event status and Google authentication
    const syncButton = document.getElementById('sync-to-google-btn');
    const calendarEl = document.getElementById('calendar');
    const isAuthenticated = calendarEl && calendarEl.getAttribute('data-is-authenticated') === 'true';

    // Only show the sync button if:
    // 1. It's a local event (not from Google)
    // 2. User is authenticated with Google
    // 3. Event doesn't have a Google event ID already
    const isLocalEvent = !event.isGoogleEvent && !window.currentEditingGoogleEvent;
    const hasGoogleId = event.google_event_id || eventData.extendedProps.google_event_id;

    if (isLocalEvent && isAuthenticated && !hasGoogleId) {
        syncButton.classList.remove('hidden');

        // Add click handler for sync button
        syncButton.onclick = async function(e) {
            e.preventDefault();

            // Show loading state
            const originalText = syncButton.innerHTML;
            syncButton.disabled = true;
            syncButton.innerHTML = `
                <svg class="animate-spin h-4 w-4 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Syncing...
            `;

            try {
                const eventId = document.getElementById('edit-event-id').value;
                const response = await fetch(`${window.baseUrl}/events/${eventId}/sync-to-google`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const result = await response.json();

                if (response.ok) {
                    // If there's a redirect URL, user needs to authenticate
                    if (result.redirect) {
                        Swal.fire({
                            title: 'Authentication Required',
                            text: result.message || 'You need to authenticate with Google Calendar',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#22c55e',
                            confirmButtonText: 'Connect Now',
                            cancelButtonText: 'Cancel'
                        }).then((swalResult) => {
                            if (swalResult.isConfirmed) {
                                window.location.href = result.redirect;
                            }
                        });
                    } else if (result.alreadySynced) {
                        // Already synced
                        Swal.fire({
                            icon: 'info',
                            title: 'Already Synced',
                            text: result.message || 'This event is already synced with Google Calendar',
                            confirmButtonColor: '#22c55e'
                        });
                        // Hide the sync button
                        syncButton.classList.add('hidden');
                    } else {
                        // Success
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: result.message || 'Event synced successfully to Google Calendar!',
                            confirmButtonColor: '#22c55e'
                        }).then(() => {
                            // Hide the sync button after successful sync
                            syncButton.classList.add('hidden');
                            // Refresh calendar
                            if (window.calendar && typeof window.calendar.refetchEvents === 'function') {
                                window.calendar.refetchEvents();
                            }
                        });
                    }
                } else {
                    throw new Error(result.message || 'Failed to sync event');
                }
            } catch (error) {
                console.error('Error syncing event to Google Calendar:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Sync Failed',
                    text: error.message || 'Failed to sync event. Please try again.',
                    confirmButtonColor: '#22c55e'
                });
            } finally {
                // Restore button state
                syncButton.disabled = false;
                syncButton.innerHTML = originalText;
            }
        };
    } else {
        syncButton.classList.add('hidden');
    }

    // If Google event but not authenticated, show warning
    if (window.currentEditingGoogleEvent) {
        const calendarEl = document.getElementById('calendar');
        const isAuthenticated = calendarEl && calendarEl.getAttribute('data-is-authenticated') === 'true';
        if (!isAuthenticated) {
            window.closeEditModal();
            Swal.fire({
                title: 'Google Authentication Required',
                text: 'You need to connect your Google account to edit Google Calendar events',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#22c55e',
                confirmButtonText: 'Connect Google Account',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ route('google.auth') }}";
                }
            });
            return;
        }
    }

    // For non-institute users, check if they can edit this event
    const userDivision = "{{ auth()->user()->division }}";
    const userIsDivisionHead = {{ auth()->user()->is_division_head ? 'true' : 'false' }};
    const eventDivision = eventData.extendedProps.calendarType ||
                         (eventData.extendedProps.calendar_type || 'institute');

    // For division heads, extract their sector from their division
    const userSector = userDivision.split('_')[0]; // e.g., sector1_div1 -> sector1

    // Access check
    if (userDivision !== 'institute') {
        let hasAccess = false;

        if (userIsDivisionHead) {
            // Division heads can access their division and sector
            hasAccess = eventDivision === userDivision || eventDivision === userSector;
        } else {
            // Regular users can only access their division
            hasAccess = eventDivision === userDivision;
        }

        if (!hasAccess) {
            Swal.fire({
                icon: 'error',
                title: 'Permission Denied',
                text: 'You do not have permission to edit events in this division.',
                confirmButtonColor: '#22c55e'
            });
            return;
        }
    }
};

// Also make closeEditModal globally available
window.closeEditModal = function() {
    const modal = document.getElementById('edit-event-modal');
    modal.classList.add('translate-x-full');
    document.getElementById('calendar-container').classList.remove('mr-120');

    // Remove backdrop
    const backdrop = document.getElementById('edit-backdrop');
    if (backdrop) {
        backdrop.remove();
    }

    document.body.style.overflow = '';
};

function createEditGuestTag(email) {
    const guestContainer = document.getElementById('edit-guest-container');
    const guestInput = document.getElementById('edit-guest-input');

    const span = document.createElement("span");
    span.className = "px-2 py-1 bg-gray-100 rounded-full text-sm flex items-center group";
    span.textContent = email;

    const removeBtn = document.createElement("button");
    removeBtn.innerHTML = "&times;";
    removeBtn.className = "ml-2 text-gray-400 hover:text-red-500 transition-colors";
    removeBtn.onclick = function() {
        editGuests = editGuests.filter(g => g !== email);
        span.remove();
        updateEditHiddenInput();
    };

    span.appendChild(removeBtn);
    guestContainer.insertBefore(span, guestInput);
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('edit-event-form');
    const guestInput = document.getElementById('edit-guest-input');

    // Color option handling
    document.querySelectorAll(".edit-color-option input").forEach(option => {
        option.addEventListener("change", function() {
            document.querySelectorAll(".edit-color-option").forEach(el => {
                el.classList.remove("ring-4", "ring-offset-2", "ring-blue-300");
            });
            this.parentElement.classList.add("ring-4", "ring-offset-2", "ring-blue-300");
        });
    });

    // Prevent Enter key submission except for guest input
    form.querySelectorAll('input:not(#edit-guest-input)').forEach(input => {
        input.addEventListener('keydown', function(event) {
            if (event.key === "Enter") event.preventDefault();
        });
    });

    // Guest input handling
    guestInput.addEventListener('keydown', async function(event) {
        if (event.key !== "Enter") return;
        event.preventDefault();

        const email = this.value.trim();
        if (!email) return;

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            this.classList.add('border-red-500');
            alert('Please enter a valid email address');
            return;
        }

        if (!editGuests.includes(email)) {
            editGuests.push(email);
            createEditGuestTag(email);
            document.getElementById('edit-guest-hidden').value = JSON.stringify(editGuests);
        }
        this.value = "";
        this.classList.remove('border-red-500');
    });

    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Handle pending email input first
        const emailInput = document.getElementById('edit-guest-input');
        const email = emailInput.value.trim();
        if (email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(email) && !editGuests.includes(email)) {
                editGuests.push(email);
                createEditGuestTag(email);
                document.getElementById('edit-guest-hidden').value = JSON.stringify(editGuests);
                emailInput.value = "";
            }
        }

        const eventId = document.getElementById('edit-event-id').value;
        const formData = new FormData(this);

        try {
            // Check for guest scheduling conflicts
            if (editGuests.length > 0) {
                const startDate = document.getElementById('edit-start-date').value;
                const endDate = document.getElementById('edit-end-date').value || startDate;
                const checkData = new URLSearchParams();
                checkData.append('guests', JSON.stringify(editGuests));
                checkData.append('start_date', startDate);
                checkData.append('end_date', endDate);
                checkData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                checkData.append('event_id', eventId);

                const checkResponse = await fetch(`${window.baseUrl}/check-conflicts`, {
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
                    });

                    const result = await Swal.fire({
                        title: 'Schedule Conflict Detected',
                        html: conflictHtml,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#22c55e',
                        cancelButtonColor: '#ef4444',
                        confirmButtonText: 'Update anyway',
                        cancelButtonText: 'Cancel'
                    });

                    if (!result.isConfirmed) {
                        return;
                    }
                }
            }

            // Check if this is a Google event
            let response;
            console.log('Updating event, is Google event:', window.currentEditingGoogleEvent, 'Event ID:', eventId);
            if (window.currentEditingGoogleEvent) {
                // Extract the actual Google event ID
                const googleEventId = eventId.replace('google_', '');
                console.log('Updating Google event with ID:', googleEventId);

                try {
                    // Debug the contents of formData
                    console.log("Form data being sent for Google Calendar update:");
                    for (let pair of formData.entries()) {
                        console.log(pair[0] + ': ' + pair[1]);
                    }

                    response = await window.googleCalendar.updateEvent(googleEventId, formData);
                    console.log('Google update response:', response);
                } catch (error) {
                    console.error('Error with Google update:', error);
                    throw error;
                }
            } else {
                // Regular event update
                console.log('Sending update for regular event ID:', eventId);
                console.log('Form data being sent:');
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                try {
                    // Send the request
                    const fetchResponse = await fetch(`${window.baseUrl}/events/${eventId}`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });

                    // Get the response text first
                    const responseText = await fetchResponse.text();
                    console.log('Server response text:', responseText);

                    // Create a response-like object with the parsed data
                    response = {
                        ok: fetchResponse.ok,
                        status: fetchResponse.status,
                        success: false,
                        message: ''
                    };

                    if (responseText) {
                        try {
                            const responseData = JSON.parse(responseText);
                            response.success = responseData.success;
                            response.message = responseData.message;
                            response.data = responseData;
                            console.log('Parsed response data:', responseData);
                        } catch (jsonError) {
                            console.error('Error parsing JSON response:', jsonError);
                            // If we can't parse JSON but the request was successful, consider it a success
                            if (fetchResponse.ok) {
                                response.success = true;
                                response.message = 'Event updated successfully';
                            }
                        }
                    }
                } catch (fetchError) {
                    console.error('Fetch error:', fetchError);
                    throw fetchError;
                }
            }

            if (response.ok || (response.success !== undefined && response.success)) {
                // Force immediate refresh before closing modal and showing alert
                if (window.calendar && typeof window.calendar.refetchEvents === 'function') {
                    console.log('Calling calendar.refetchEvents()');
                    try {
                        window.calendar.refetchEvents();
                    } catch (refreshError) {
                        console.error('Error refreshing calendar:', refreshError);
                    }
                }

                // Close modal
                window.closeEditModal();

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message || 'Event updated successfully!',
                    confirmButtonColor: '#22c55e'
                }).then(() => {
                    console.log('Refreshing calendar after confirmation');
                    // Force reload after confirmation - fail-safe
                    if (window.calendar && typeof window.calendar.refetchEvents === 'function') {
                        window.calendar.refetchEvents();
                    } else {
                        console.log('Reloading page as fallback');
                        location.reload();
                    }
                });
            } else {
                // Handle error using the response object we created, not trying to read the body again
                const errorMessage = response.message || response.data?.error || 'Failed to update event';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                    confirmButtonColor: '#22c55e'
                });
            }
        } catch (error) {
            console.error('Error updating event:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to update event. Please try again.',
                confirmButtonColor: '#22c55e'
            });
        }
    });

    // Replace the click outside handler
    document.addEventListener('mousedown', function(event) {
        if (document.querySelector('.swal2-container')) {
            return;
        }
        const modal = document.getElementById('edit-event-modal');
        const modalContent = modal.querySelector('.h-full.bg-white');

        if (modal && !modal.classList.contains('translate-x-full') && !modalContent.contains(event.target)) {
            window.closeEditModal();
        }
    }, true);
});
</script>

