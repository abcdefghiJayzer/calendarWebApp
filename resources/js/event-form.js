document.addEventListener('DOMContentLoaded', function() {
    const addEventForm = document.getElementById('add-event-form');
    const editEventForm = document.getElementById('edit-event-form');

    function validateOrganizationalUnits(form) {
        const isGlobal = form.querySelector('input[name="is_global"]').checked;
        const selectedUnits = form.querySelectorAll('input[name="organizational_unit_ids[]"]:checked');

        if (!isGlobal && selectedUnits.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please select at least one organizational unit or make the event global',
                confirmButtonColor: '#22c55e'
            });
            return false;
        }
        return true;
    }

    async function handleOverlappingEvents(data, formData, isEdit = false) {
        // Format the conflict information for display
        let conflictHtml = 'This time slot overlaps with the following priority events:<br><br>';

        data.overlapping_events.forEach(event => {
            const startDate = new Date(event.start_date).toLocaleString();
            const endDate = new Date(event.end_date).toLocaleString();
            conflictHtml += `<strong>${event.title}</strong><br>`;
            conflictHtml += `From: ${startDate}<br>`;
            conflictHtml += `To: ${endDate}<br>`;
            if (event.organizational_units && event.organizational_units.length > 0) {
                conflictHtml += `Organizational Units: ${event.organizational_units.join(', ')}<br>`;
            }
            conflictHtml += '<br>';
        });

        // Show confirmation dialog with options
        const result = await Swal.fire({
            title: 'Priority Event Conflict',
            html: conflictHtml,
            icon: 'warning',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonColor: '#22c55e',
            denyButtonColor: '#3b82f6',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Proceed anyway',
            denyButtonText: 'Edit',
            cancelButtonText: 'Cancel'
        });

        if (result.isConfirmed) {
            // User wants to proceed despite conflicts
            try {
                // Add parameter to force creation despite overlaps
                formData.append(isEdit ? 'force_update' : 'force_create', 'true');

                const response = await fetch(isEdit ? `${window.baseUrl}/events/${formData.get('id')}` : `${window.baseUrl}/events`, {
                    method: isEdit ? 'POST' : 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const responseData = await response.json();

                if (response.ok) {
                    if (isEdit) {
                        closeEditModal();
                    } else {
                        closeModal();
                    }
                    if (window.calendar) {
                        window.calendar.refetchEvents();
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: responseData.message || (isEdit ? 'Event updated successfully!' : 'Event created successfully!'),
                        confirmButtonColor: '#22c55e'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: responseData.error || responseData.message || (isEdit ? 'Failed to update event' : 'Failed to create event'),
                        confirmButtonColor: '#22c55e'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'There was a problem with the request. Please try again.',
                    confirmButtonColor: '#22c55e'
                });
            }
        }
    }

    if (addEventForm) {
        addEventForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!validateOrganizationalUnits(this)) {
                return;
            }

            const formData = new FormData(this);

            try {
                const response = await fetch(`${window.baseUrl}/events`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    closeModal();
                    if (window.calendar) {
                        window.calendar.refetchEvents();
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message || 'Event created successfully!',
                        confirmButtonColor: '#22c55e'
                    });
                } else {
                    // Check specifically for overlapping priority events (422 status)
                    if (response.status === 422 && data.overlapping_events) {
                        await handleOverlappingEvents(data, formData);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error || data.message || 'Failed to create event',
                            confirmButtonColor: '#22c55e'
                        });
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'There was a problem creating the event. Please try again.',
                    confirmButtonColor: '#22c55e'
                });
            }
        });
    }

    if (editEventForm) {
        editEventForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!validateOrganizationalUnits(this)) {
                return;
            }

            const formData = new FormData(this);
            const eventId = formData.get('id');

            try {
                const response = await fetch(`${window.baseUrl}/events/${eventId}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    closeEditModal();
                    if (window.calendar) {
                        window.calendar.refetchEvents();
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message || 'Event updated successfully!',
                        confirmButtonColor: '#22c55e'
                    });
                } else {
                    // Check specifically for overlapping priority events (422 status)
                    if (response.status === 422 && data.overlapping_events) {
                        await handleOverlappingEvents(data, formData, true);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error || data.message || 'Failed to update event',
                            confirmButtonColor: '#22c55e'
                        });
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'There was a problem updating the event. Please try again.',
                    confirmButtonColor: '#22c55e'
                });
            }
        });
    }
});
