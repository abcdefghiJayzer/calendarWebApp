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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Network response was not ok');
                }

                const result = await response.json();

                if (result.success) {
                    closeModal();
                    if (window.calendar) {
                        window.calendar.refetchEvents();
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Event created successfully!',
                        confirmButtonColor: '#22c55e'
                    });
                }
            } catch (error) {
                console.error('Error:', error);

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'There was a problem creating the event',
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
                    method: 'PUT',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Network response was not ok');
                }

                const result = await response.json();

                if (result.success) {
                    closeEditModal();
                    if (window.calendar) {
                        window.calendar.refetchEvents();
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Event updated successfully!',
                        confirmButtonColor: '#22c55e'
                    });
                }
            } catch (error) {
                console.error('Error:', error);

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'There was a problem updating the event',
                    confirmButtonColor: '#22c55e'
                });
            }
        });
    }
});
