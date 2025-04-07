<div id="edit-event-modal" class="fixed inset-0 z-[999] overflow-y-auto hidden">
    <div onclick="closeEditModal()" class="fixed inset-0 bg-black/60"></div>

    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="relative bg-white rounded-lg p-6 w-full max-w-lg shadow-xl transform transition-all">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Edit Event</h2>
                <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="edit-event-form" class="space-y-4">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <input type="hidden" id="edit-event-id" name="id">

                <label for="edit-color" class="block text-sm font-medium text-gray-700">Choose Event Color:</label>
                <div class="flex space-x-3 mt-2">
                    <?php $__currentLoopData = [
                    '#3b82f6' => 'bg-blue-500',
                    '#ef4444' => 'bg-red-500',
                    '#eab308' => 'bg-yellow-500',
                    '#22c55e' => 'bg-green-500',
                    '#000000' => 'bg-black'
                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hex => $bg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="cursor-pointer edit-color-option rounded-full" data-color="<?php echo e($hex); ?>">
                        <input type="radio" name="color" value="<?php echo e($hex); ?>" class="hidden">
                        <div class="w-8 h-8 rounded-full border-2 border-gray-300 <?php echo e($bg); ?>"></div>
                    </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <div>
                    <label for="edit-title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" name="title" id="edit-title" required
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>

                <div>
                    <label for="edit-description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="edit-description"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500"></textarea>
                </div>

                <div>
                    <label for="edit-start-date" class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input type="datetime-local" name="start_date" id="edit-start-date" required
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>

                <div>
                    <label for="edit-end-date" class="block text-sm font-medium text-gray-700">End Date</label>
                    <input type="datetime-local" name="end_date" id="edit-end-date" required
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700">Edit guests</label>
                    <div id="edit-guest-container" class="flex flex-wrap gap-2 border p-2 rounded">
                        <input id="edit-guest-input" type="email" class="outline-none border-none flex-grow p-1"
                            placeholder="Type email and press Enter">
                    </div>
                    <input type="hidden" name="guests" id="edit-guest-hidden" value="[]">
                </div>

                <div>
                    <label for="edit-location" class="block text-sm font-medium text-gray-700">Location</label>
                    <input type="text" name="location" id="edit-location"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>

                <div class="mb-4">
                    <label for="edit-calendar_type" class="block text-sm font-medium text-gray-700">Calendar Type</label>
                    <select name="calendar_type" id="edit-calendar_type" required
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="institute">Institute Level</option>
                        <option value="sectoral">Sectoral</option>
                        <option value="division">Division</option>
                    </select>
                </div>

                <div class="flex items-center">
                    <input type="hidden" name="is_all_day" value="0">
                    <input type="checkbox" name="is_all_day" id="edit-is-all-day" value="1"
                        class="text-green-600 focus:ring-green-500 rounded">
                    <label for="edit-is-all-day" class="ml-2 text-sm text-gray-700">All Day Event</label>
                </div>

                <div class="flex items-center mt-2">
                    <input type="hidden" name="private" value="0">
                    <input type="checkbox" name="private" id="edit-private" value="1"
                        class="text-green-600 focus:ring-green-500 rounded">
                    <label for="edit-private" class="ml-2 text-sm text-gray-700">Private Event</label>
                </div>

                <input type="hidden" name="is_google_event" id="edit-is-google-event" value="0">
                <input type="hidden" name="google_event_id" id="edit-google-event-id" value="">

                <div class="flex justify-end space-x-2">
                    <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Update Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let editGuests = [];
let isGoogleEvent = false;

function openEditModal(event) {
    console.log('Opening edit modal with event:', event);

    document.getElementById('edit-event-modal').classList.remove('hidden');
    document.getElementById('edit-event-id').value = event.id;

    // Set hidden fields for Google events
    isGoogleEvent = event.id.startsWith('google_') || event.extendedProps.source === 'google';
    document.getElementById('edit-is-google-event').value = isGoogleEvent ? '1' : '0';

    if (isGoogleEvent) {
        // Store the Google event ID without the prefix
        const googleEventId = event.id.replace('google_', '');
        document.getElementById('edit-google-event-id').value = googleEventId;
        console.log('Google event detected, ID:', googleEventId);
    }

    // Handle private event visibility
    if (event.extendedProps.private && event.extendedProps.user_id !== {{ auth()->id() }}) {
        document.getElementById('edit-title').value = 'Private Event';
        document.getElementById('edit-description').value = '';
        document.getElementById('edit-location').value = '';
        document.getElementById('edit-guest-container').style.display = 'none';
    } else {
        document.getElementById('edit-title').value = event.title;
        document.getElementById('edit-description').value = event.extendedProps.description || '';
        document.getElementById('edit-location').value = event.extendedProps.location || '';
        document.getElementById('edit-guest-container').style.display = 'block';

        // Set private checkbox
        document.getElementById('edit-private').checked = event.extendedProps.private || false;
    }

    document.getElementById('edit-is-all-day').checked = event.allDay;

    // Set start and end dates
    const startDate = new Date(event.start);
    const endDate = event.end ? new Date(event.end) : new Date(startDate);

    document.getElementById('edit-start-date').value = startDate.toISOString().slice(0, 16);
    document.getElementById('edit-end-date').value = endDate.toISOString().slice(0, 16);

    // Set color with highlighting
    console.log('Event background color:', event.backgroundColor);
    const colorValue = event.backgroundColor || '#3b82f6';
    const colorOption = document.querySelector(`.edit-color-option[data-color="${colorValue}"]`);

    if (colorOption) {
        const colorInput = colorOption.querySelector('input');
        colorInput.checked = true;
        // Clear previous highlights
        document.querySelectorAll(".edit-color-option").forEach(el => {
            el.classList.remove("ring-4", "ring-offset-2", "ring-blue-300");
        });
        // Add highlight to selected color
        colorOption.classList.add("ring-4", "ring-offset-2", "ring-blue-300");
    }

    // Set guests
    editGuests = event.extendedProps.guests || [];
    document.getElementById('edit-guest-hidden').value = JSON.stringify(editGuests);
    const guestContainer = document.getElementById('edit-guest-container');
    const guestInput = document.getElementById('edit-guest-input');

    // Clear existing guest tags
    Array.from(guestContainer.children).forEach(child => {
        if (child !== guestInput) child.remove();
    });

    // Create guest tags
    editGuests.forEach(email => createEditGuestTag(email));
}

function closeEditModal() {
    document.getElementById('edit-event-modal').classList.add('hidden');
}

function createEditGuestTag(email) {
    const container = document.getElementById('edit-guest-container');
    const input = document.getElementById('edit-guest-input');

    const span = document.createElement("span");
    span.className = "px-2 py-1 bg-gray-200 rounded text-sm flex items-center";
    span.textContent = email;

    const removeBtn = document.createElement("button");
    removeBtn.innerHTML = "&times;";
    removeBtn.className = "ml-2 text-red-500 hover:text-red-700";
    removeBtn.onclick = function() {
        editGuests = editGuests.filter(g => g !== email);
        span.remove();
        document.getElementById('edit-guest-hidden').value = JSON.stringify(editGuests);
    };

    span.appendChild(removeBtn);
    container.insertBefore(span, input);
}

document.getElementById('edit-guest-input').addEventListener('keydown', function(event) {
    if (event.key === "Enter") {
        event.preventDefault(); // Only prevent default for the email input
        const email = this.value.trim();
        if (email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(email)) {
                if (!editGuests.includes(email)) {
                    editGuests.push(email);
                    createEditGuestTag(email);
                    document.getElementById('edit-guest-hidden').value = JSON.stringify(editGuests);
                }
                this.value = "";
                this.classList.remove('border-red-500');
            } else {
                this.classList.add('border-red-500');
                alert('Please enter a valid email address');
            }
        }
    }
});

// Prevent form submission on Enter key in other inputs except the guest email input
document.getElementById('edit-event-form').querySelectorAll('input:not(#edit-guest-input)').forEach(input => {
    input.addEventListener('keydown', function(event) {
        if (event.key === "Enter") {
            event.preventDefault();
        }
    });
});

// Add form submit handler for pending email input
document.getElementById('edit-event-form').addEventListener('submit', async function(e) {
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
            checkData.append('event_id', eventId); // Add event ID to exclude current event from conflict check

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
                const result = await Swal.fire({
                    title: 'Schedule Conflict Detected',
                    html: `The following guests already have events during this time:<br><strong>${conflictResult.conflicts.join(', ')}</strong>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#22c55e',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: 'Update anyway',
                    cancelButtonText: 'Cancel'
                });

                if (!result.isConfirmed) {
                    return; // Return without closing modal
                }
            }
        }

        // Check if this is a Google event
        let response;
        const isGoogleEventValue = document.getElementById('edit-is-google-event').value === '1';
        console.log('Updating event, is Google event:', isGoogleEventValue, 'Event ID:', eventId);

        if (isGoogleEventValue) {
            // Use the stored Google event ID without prefix
            const googleEventId = document.getElementById('edit-google-event-id').value || eventId.replace('google_', '');
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
            response = await fetch(`/OJT/calendarWebApp/events/${eventId}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
        }

        if (response.ok) {
            closeEditModal();
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Event updated successfully!',
                confirmButtonColor: '#22c55e'
            }).then(() => {
                location.reload();
            });
        } else {
            const errorData = await response.json();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorData.message || 'Failed to update event',
                confirmButtonColor: '#22c55e'
            });
        }
    } catch (error) {
        console.error('Error updating event:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to update event. Please try again.',
            confirmButtonColor: '#22c55e'
        });
    }
});

document.querySelectorAll(".edit-color-option input").forEach(option => {
    option.addEventListener("change", function() {
        document.querySelectorAll(".edit-color-option").forEach(el => {
            el.classList.remove("ring-4", "ring-offset-2", "ring-blue-300");
        });
        this.parentElement.classList.add("ring-4", "ring-offset-2", "ring-blue-300");
    });
});

// Replace the click outside handler
document.addEventListener('mousedown', function(event) {
    // Don't close if SweetAlert is visible
    if (document.querySelector('.swal2-container')) {
        return;
    }

    const modal = document.getElementById('edit-event-modal');
    const modalContent = modal.querySelector('.relative.bg-white');

    if (modal && !modal.classList.contains('hidden') && !modalContent.contains(event.target)) {
        closeEditModal();
    }
}, true);
</script>
<?php /**PATH C:\xampp\htdocs\OJT\calendarWebApp\resources\views/components/edit-event-modal.blade.php ENDPATH**/ ?>
