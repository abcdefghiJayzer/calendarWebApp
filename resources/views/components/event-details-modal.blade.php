<div id="event-details-modal" class="fixed inset-0 z-[999] overflow-y-auto hidden">
    <!-- Backdrop -->
    <div onclick="closeEventModal()" class="fixed inset-0 bg-black/60"></div>

    <!-- Modal content -->
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="relative bg-white rounded-lg p-6 w-full max-w-lg shadow-xl transform transition-all">
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

        modal.classList.remove('hidden');
    }

    function closeEventModal() {
        document.getElementById('event-details-modal').classList.add('hidden');
        currentEventId = null;
    }

    function editEvent() {
        if (!currentEventId) return;
        // Get current event data and open edit modal
        fetch(`/OJT/calendarWebApp/events/${currentEventId}`)
            .then(response => response.json())
            .then(data => {
                openEditModal(data);
            })
            .catch(error => console.error('Error:', error));

        closeEventModal();
    }

    async function deleteEvent() {
        if (!currentEventId || !confirm('Are you sure you want to delete this event?')) return;

        try {
            await fetch(`/OJT/calendarWebApp/events/${currentEventId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            // Always close modal and refresh the page
            closeEventModal();
            location.reload();

        } catch (error) {
            console.error('Error:', error);
            closeEventModal();
            location.reload();
        }
    }

    // Add click outside handler after existing code
    document.addEventListener('mousedown', function(event) {
        const modal = document.getElementById('event-details-modal');
        const modalContent = modal.querySelector('.relative.bg-white');

        if (modal && !modal.classList.contains('hidden') && !modalContent.contains(event.target)) {
            closeEventModal();
        }
    }, true);
</script>
