<div id="event-details-modal" class="fixed inset-y-0 right-0 z-[999] w-120 transform translate-x-full transition-transform duration-300 ease-in-out">
    <div class="h-full bg-white shadow-xl shadow-black/10">
        <div class="p-10 h-full overflow-y-auto shadow-[-8px_0_15px_-3px_rgba(0,0,0,0.1)]">
            <div class="flex justify-between items-center mb-4">
                <h2 id="event-title" class="text-xl font-bold"></h2>
                <button onclick="closeEventModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div id="event-content" class="space-y-4">
                <!-- Content will be dynamically populated -->
            </div>

            <div class="flex justify-end space-x-2 mt-4">
                <button onclick="editEvent()" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">
                    Edit
                </button>
                <button onclick="deleteEvent()" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentEventId = null;

    function openEventModal(event) {
        currentEventId = event.id;
        const modal = document.getElementById('event-details-modal');
        modal.classList.remove('translate-x-full');
        document.getElementById('calendar-container').classList.add('mr-120');

        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.id = 'details-backdrop';
        backdrop.className = 'fixed inset-0 bg-black/20 z-[998] transition-opacity duration-300';
        backdrop.onclick = closeEventModal;
        document.body.appendChild(backdrop);

        // Prevent body scroll
        document.body.style.overflow = 'hidden';

        const title = document.getElementById('event-title');
        const content = document.getElementById('event-content');

        title.textContent = event.title;

        const startDate = new Date(event.start).toLocaleString();
        const endDate = event.end ? new Date(event.end).toLocaleString() : 'Not specified';

        content.innerHTML = `
            <p><strong>Description:</strong> ${event.extendedProps.description || 'No description'}</p>
            <p><strong>Start:</strong> ${startDate}</p>
            <p><strong>End:</strong> ${endDate}</p>
            <p><strong>Location:</strong> ${event.extendedProps.location || 'No location'}</p>
            <p><strong>All Day:</strong> ${event.allDay ? 'Yes' : 'No'}</p>
            ${event.extendedProps.guests ? `
            <div class="mt-4">
                <strong>Guests:</strong>
                <ul class="list-disc ml-5">
                    ${event.extendedProps.guests.map(guest => `<li>${guest}</li>`).join('')}
                </ul>
            </div>` : ''}
        `;
    }

    function closeEventModal() {
        const modal = document.getElementById('event-details-modal');
        modal.classList.add('translate-x-full');
        document.getElementById('calendar-container').classList.remove('mr-120');

        // Remove backdrop
        const backdrop = document.getElementById('details-backdrop');
        if (backdrop) {
            backdrop.remove();
        }

        // Restore body scroll
        document.body.style.overflow = '';
        currentEventId = null;
    }

    function editEvent() {
        if (!currentEventId) return;

        // Check if this is a Google event by looking for the 'google_' prefix
        const isGoogleEvent = currentEventId.startsWith('google_');

        if (isGoogleEvent) {
            // For Google events, don't fetch from server, use the data already available from the calendar
            const googleEventId = currentEventId.replace('google_', '');
            console.log('Editing Google Calendar event:', googleEventId);

            // Get all events from the calendar
            const allEvents = window.calendar.getEvents();

            // Find the matching Google event
            const googleEvent = allEvents.find(e => e.id === currentEventId);

            if (googleEvent) {
                console.log('Found Google event data:', googleEvent);
                const eventData = {
                    id: googleEvent.id,
                    title: googleEvent.title,
                    start: googleEvent.start,
                    end: googleEvent.end,
                    allDay: googleEvent.allDay,
                    backgroundColor: googleEvent.backgroundColor,
                    isGoogleEvent: true,  // Mark explicitly as Google event
                    extendedProps: {
                        description: googleEvent.extendedProps.description || '',
                        location: googleEvent.extendedProps.location || '',
                        guests: googleEvent.extendedProps.guests || [],
                        calendar_type: googleEvent.extendedProps.calendarType || 'institute',
                        private: googleEvent.extendedProps.private || false,
                        user_id: googleEvent.extendedProps.user_id || null
                    }
                };

                // Open edit modal with this data
                openEditModal(eventData);

                // Close the details modal
                closeEventModal();
            } else {
                console.error('Google Calendar event not found in calendar events');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Could not find Google Calendar event data',
                    confirmButtonColor: '#22c55e'
                });
            }
        } else {
            // Regular event - fetch from server as before
            fetch(`${window.baseUrl}/events/${currentEventId}`)
                .then(response => response.json())
                .then(data => {
                    // First open edit modal, then close details modal
                    data.isGoogleEvent = isGoogleEvent;
                    console.log('Data before opening edit modal:', data);
                    openEditModal(data);

                    // Remove backdrop and modal without affecting calendar container
                    const modal = document.getElementById('event-details-modal');
                    modal.classList.add('translate-x-full');
                    const backdrop = document.getElementById('details-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    document.body.style.overflow = '';
                    currentEventId = null;
                })
                .catch(error => console.error('Error:', error));
        }
    }

    async function deleteEvent() {
        if (!currentEventId) return;

        // Check if this is a Google event by looking for the 'google_' prefix
        const isGoogleEvent = currentEventId.startsWith('google_');

        if (isGoogleEvent) {
            // Check if user is authenticated with Google
            const calendarEl = document.getElementById('calendar');
            const isAuthenticated = calendarEl && calendarEl.getAttribute('data-is-authenticated') === 'true';

            if (!isAuthenticated) {
                Swal.fire({
                    title: 'Google Authentication Required',
                    text: 'You need to connect your Google account to delete Google Calendar events',
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

        const result = await Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#22c55e',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Yes, delete it!'
        });

        if (result.isConfirmed) {
            try {
                if (isGoogleEvent) {
                    console.log('Deleting Google Calendar event:', currentEventId);
                    // Extract the actual Google event ID
                    const googleEventId = currentEventId.replace('google_', '');

                    try {
                        // Use the correct path with baseUrl for consistency
                        const response = await window.googleCalendar.deleteEvent(googleEventId);
                        console.log('Delete response:', response);

                        closeEventModal();
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Event deleted successfully!',
                            confirmButtonColor: '#22c55e'
                        }).then(() => {
                            location.reload();
                        });
                    } catch (error) {
                        console.error('Error in deleteEvent:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to delete the event: ' + error.message,
                            confirmButtonColor: '#22c55e'
                        });
                    }
                } else {
                    // Regular event deletion
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `${window.baseUrl}/events/${currentEventId}`;
                    form.innerHTML = `
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}">
                        <input type="hidden" name="_method" value="DELETE">
                    `;
                    document.body.appendChild(form);
                    closeEventModal();
                    form.submit();
                }
            } catch (error) {
                console.error('Error deleting event:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to delete the event: ' + error.message,
                    confirmButtonColor: '#22c55e'
                });
            }
        }
    }

    document.addEventListener('mousedown', function(event) {
        if (document.querySelector('.swal2-container')) return;
        const modal = document.getElementById('event-details-modal');
        const modalContent = modal.querySelector('.h-full.bg-white');
        if (modal && !modal.classList.contains('translate-x-full') && !modalContent.contains(event.target)) {
            closeEventModal();
        }
    }, true);
</script>
