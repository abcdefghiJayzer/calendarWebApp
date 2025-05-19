<div id="event-details-modal" class="fixed inset-y-0 right-0 z-[999] w-120 transform translate-x-full transition-transform duration-300 ease-in-out">
    <div class="h-full bg-gray-50 shadow-xl shadow-black/10">
        <div class="p-8 h-full overflow-y-auto shadow-[-8px_0_15px_-3px_rgba(0,0,0,0.1)]">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 id="event-title" class="text-xl font-semibold text-gray-800"></h2>
                </div>
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
    const currentUserId = {{ auth()->id() }};
    const currentUserDivision = @json(auth()->user()->division);

    function openEventModal(event) {
        currentEventId = event.id;
        const modal = document.getElementById('event-details-modal');
        modal.classList.remove('translate-x-full');
        document.getElementById('calendar-container').classList.add('mr-120');

        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.id = 'details-backdrop';
        backdrop.className = 'fixed inset-0 bg-black/30 z-[998] transition-opacity duration-300';
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
                weekday: 'short',
                year: 'numeric', 
                month: 'short', 
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
                return `${days}d ${hours > 0 ? `${hours}h` : ''}`;
            } else if (hours > 0) {
                return `${hours}h ${minutes > 0 ? `${minutes}m` : ''}`;
            } else {
                return `${minutes}m`;
            }
        };

        // Debug logging
        console.log('Event data:', event);
        console.log('Event guests:', event.extendedProps?.guests);
        console.log('Event guests type:', typeof event.extendedProps?.guests);

        content.innerHTML = `
            <div class="space-y-5">
                <div class="p-4 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg border border-blue-100 shadow-sm">
                    <div class="flex items-center space-x-2 mb-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-blue-900">Event Schedule</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-3">
                        <div class="bg-white p-3 rounded-lg shadow-sm border border-blue-100">
                            <div class="flex items-center space-x-2">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-medium text-gray-500">Starts</h4>
                                    <p class="text-sm font-semibold text-gray-900">${formatDate(startDate)}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white p-3 rounded-lg shadow-sm border border-blue-100">
                            <div class="flex items-center space-x-2">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-red-100 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-medium text-gray-500">Ends</h4>
                                    <p class="text-sm font-semibold text-gray-900">${endDate ? formatDate(endDate) : 'No end time specified'}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-3 rounded-lg shadow-sm border border-blue-100">
                            <div class="flex items-center space-x-2">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-purple-100 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-medium text-gray-500">Duration</h4>
                                    <p class="text-sm font-semibold text-gray-900">${getDuration(startDate, endDate)}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Description</h3>
                    <p class="text-sm text-gray-600">${event.extendedProps?.description || 'No description provided'}</p>
                </div>

                <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Location</h3>
                    <p class="text-sm text-gray-600">${event.extendedProps?.location || 'No location specified'}</p>
                </div>

                ${event.extendedProps?.guests && Array.isArray(event.extendedProps.guests) && event.extendedProps.guests.length > 0 ? `
                    <div class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg border border-purple-100 shadow-sm">
                        <div class="flex items-center space-x-2 mb-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-purple-900">Event Guests</h3>
                                <p class="text-sm text-purple-600">${event.extendedProps.guests.length} ${event.extendedProps.guests.length === 1 ? 'guest' : 'guests'}</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            ${event.extendedProps.guests.map(guest => `
                                <div class="flex items-center justify-between bg-white p-3 rounded-lg shadow-sm border border-purple-100">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                                            <span class="text-sm font-medium text-purple-600">${guest.charAt(0).toUpperCase()}</span>
                                        </div>
                                        <span class="text-sm text-gray-700">${guest}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Invited</span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : `
                    <div class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg border border-purple-100 shadow-sm">
                        <div class="flex items-center space-x-2">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-purple-900">Event Guests</h3>
                                <p class="text-sm text-gray-500">No guests for this event</p>
                            </div>
                        </div>
                    </div>
                `}
            </div>
        `;

        // Get event creator ID
        let eventCreatorId = null;
        if (event.extendedProps && event.extendedProps.user_id) {
            eventCreatorId = parseInt(event.extendedProps.user_id);
        } else if (event.user_id) {
            eventCreatorId = parseInt(event.user_id);
        }
        
        // Debug logging
        console.log('Event data:', event);
        console.log('Event creator ID:', eventCreatorId);
        console.log('Current user ID:', currentUserId);
        console.log('Comparison result:', eventCreatorId === currentUserId);

        // Show action buttons
        actionButtons.innerHTML = `
            ${eventCreatorId === currentUserId ? `
                <button onclick="editEvent()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-1.5 text-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Edit</span>
                </button>
                <button onclick="deleteEvent()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center space-x-1.5 text-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Delete</span>
                </button>
            ` : ''}
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
        const modalContent = modal.querySelector('.h-full.bg-gray-50');
        if (modal && !modal.classList.contains('translate-x-full') && !modalContent.contains(event.target)) {
            closeEventModal();
        }
    }, true);
</script>
