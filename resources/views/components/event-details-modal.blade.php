<div id="event-details-modal" class="fixed inset-y-0 right-0 z-[999] w-120 transform translate-x-full transition-transform duration-300 ease-in-out">
    <div class="h-full bg-gray-50 shadow-xl shadow-black/10">
        <div class="p-8 h-full overflow-y-auto shadow-[-8px_0_15px_-3px_rgba(0,0,0,0.1)]">
            <div class="flex justify-between items-center mb-6">
                <h2 id="event-title" class="text-xl font-semibold text-gray-800"></h2>
                <button onclick="closeEventModal()" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div id="event-content" class="space-y-5">
                <!-- Content will be dynamically populated -->
            </div>

            <div class="flex justify-end space-x-3 pt-4" id="event-action-buttons">
                <!-- Buttons will be shown/hidden dynamically -->
            </div>
        </div>
    </div>
</div>

<script>
    let currentEventId = null;
    const currentUserId = @json(auth()->id());
    const currentUserDivision = @json(auth()->user()->division);

    function openEventModal(event) {
        currentEventId = event.id;
        const modal = document.getElementById('event-details-modal');
        modal.classList.remove('translate-x-full');
        document.getElementById('calendar-container').classList.add('mr-120');

        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.id = 'details-backdrop';
        backdrop.className = 'fixed inset-0 bg-black/3 z-[998] transition-opacity duration-300';
        backdrop.onclick = closeEventModal;
        document.body.appendChild(backdrop);

        document.body.style.overflow = 'hidden';

        const title = document.getElementById('event-title');
        const content = document.getElementById('event-content');
        const actionButtons = document.getElementById('event-action-buttons');

        title.textContent = event.title;

        const startDate = new Date(event.start);
        const endDate = event.end ? new Date(event.end) : null;

        // Format dates for display
        const formatDate = (date) => {
            const options = { 
                weekday: 'long',
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return date.toLocaleDateString('en-US', options);
        };

        // Calculate duration
        const getDuration = (start, end) => {
            if (!end) return 'No end time specified';
            
            const diff = end - start;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            if (days > 0) {
                return `${days} day${days > 1 ? 's' : ''} ${hours > 0 ? `and ${hours} hour${hours > 1 ? 's' : ''}` : ''}`;
            } else if (hours > 0) {
                return `${hours} hour${hours > 1 ? 's' : ''} ${minutes > 0 ? `and ${minutes} minute${minutes > 1 ? 's' : ''}` : ''}`;
            } else {
                return `${minutes} minute${minutes > 1 ? 's' : ''}`;
            }
        };

        // Debug event object to see what we're working with
        console.log('Event object:', event);

        content.innerHTML = `
            <div class="space-y-4">
                <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Description</h3>
                    <p class="text-gray-600">${event.extendedProps.description || 'No description'}</p>
                </div>

                <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Event Schedule</h3>
                    <div class="space-y-3">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Start</p>
                                <p class="text-sm text-gray-600">${formatDate(startDate)}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">End</p>
                                <p class="text-sm text-gray-600">${endDate ? formatDate(endDate) : 'No end time specified'}</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Duration</p>
                                <p class="text-sm text-gray-600">${getDuration(startDate, endDate)}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Location</h3>
                    <p class="text-gray-600">${event.extendedProps.location || 'No location'}</p>
                </div>

                <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Event Type</h3>
                    <p class="text-gray-600">${event.allDay ? 'All Day Event' : 'Timed Event'}</p>
                </div>

                ${event.extendedProps.guests && event.extendedProps.guests.length > 0 ? `
                    <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Guests</h3>
                        <div class="flex flex-wrap gap-2">
                            ${event.extendedProps.guests.map(guest => `
                                <span class="px-2 py-1 bg-gray-100 rounded-full text-sm text-gray-600">
                                    ${guest}
                                </span>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;

        // Get event creator ID
        let eventCreatorId = event.extendedProps?.user_id || event.user_id || null;

        // Remove the permission check entirely and always show action buttons
        actionButtons.innerHTML = `
            <button onclick="editEvent()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                Edit
            </button>
            <button onclick="deleteEvent()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                Delete
            </button>
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

        // Detect Google event by ID or source
        const hasGooglePrefix = currentEventId.startsWith('google_');
        const isGoogleFormatId = /^[a-z0-9]{26,}$/.test(currentEventId);
        const isGoogleEvent = hasGooglePrefix || isGoogleFormatId;

        if (isGoogleEvent) {
            // For Google events, show a warning and do not open the edit modal
            Swal.fire({
                icon: 'warning',
                title: 'Not Editable',
                text: 'Google Calendar events cannot be edited from this app.',
                confirmButtonColor: '#22c55e'
            });
            return;
        }

        // For local events, fetch and open the edit modal as usual
        fetch(`${window.baseUrl}/events/${currentEventId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                data.isGoogleEvent = false;
                if (!data.extendedProps && data.calendar_type) {
                    data.extendedProps = {
                        description: data.description || '',
                        location: data.location || '',
                        guests: data.guests || [],
                        calendar_type: data.calendar_type || 'division',
                        private: data.private || false,
                        user_id: data.user_id || null
                    };
                }
                closeEventModal();
                setTimeout(() => {
                    window.openEditModal(data);
                }, 100);
            })
            .catch(error => {
                console.error('Error fetching event data:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load event data',
                    confirmButtonColor: '#22c55e'
                });
            });
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
                            window.calendar.refetchEvents();
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
                    // Replace form submission with fetch API to handle responses
                    try {
                        const response = await fetch(`${window.baseUrl}/events/${currentEventId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            }
                        });

                        const data = await response.json();

                        if (response.ok) {
                            closeEventModal();
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: data.message || 'Event deleted successfully!',
                                confirmButtonColor: '#22c55e'
                            }).then(() => {
                                window.calendar.refetchEvents();
                            });
                        } else {
                            // Handle error responses with a modal
                            Swal.fire({
                                icon: 'error',
                                title: 'Permission Denied',
                                text: data.message || 'You do not have permission to delete this event.',
                                confirmButtonColor: '#22c55e'
                            });
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
