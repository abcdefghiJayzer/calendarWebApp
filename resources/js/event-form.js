document.addEventListener('DOMContentLoaded', function() {
    const addEventForm = document.getElementById('add-event-form');

    if (addEventForm) {
        addEventForm.addEventListener('submit', async function(e) {
            e.preventDefault();

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
                    throw new Error('Network response was not ok');
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
                    text: 'There was a problem creating the event',
                    confirmButtonColor: '#22c55e'
                });
            }
        });
    }
});
