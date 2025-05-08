// import { Calendar } from '@fullcalendar/core';
// import dayGridPlugin from '@fullcalendar/daygrid';
// import timeGridPlugin from '@fullcalendar/timegrid';
// import listPlugin from '@fullcalendar/list';
// import interactionPlugin from '@fullcalendar/interaction';

// document.addEventListener('DOMContentLoaded', function () {

//     var calendarEl = document.getElementById('calendar');

//     if (calendarEl) {

//         console.log(events);
//         var calendar = new Calendar(calendarEl, {

//             plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
//             initialView: 'dayGridMonth',
//             headerToolbar: {
//                 left: 'prev,next today',
//                 center: 'title',
//                 right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth' // Buttons for views
//             },
//             events: "{{ route('getEvents') }}", // Fetch events dynamically
//             eventDidMount: function (info) {
//                 console.log(info.event);
//             }
//         });

//         calendar.render();
//     } else {
//         console.error('Calendar element not found.');
//     }
// });

function syncAllToGoogle() {
    if (!isGoogleAuthenticated) {
        Swal.fire({
            title: 'Google Calendar Not Connected',
            text: 'Please connect your Google Calendar first.',
            icon: 'warning',
            confirmButtonText: 'Connect Now',
            showCancelButton: true,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '/auth/google';
            }
        });
        return;
    }

    // Show confirmation dialog first
    Swal.fire({
        title: 'Sync All Events to Google',
        text: 'This will push all your local calendar events to Google Calendar. Already synced events will be skipped. Continue?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Sync Now',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Syncing Events',
                text: 'Please wait while we sync your events with Google Calendar...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Make the API call
            fetch('/api/calendar/sync-all-to-google', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Sync Complete',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Refresh the calendar to show updated events
                        calendar.refetchEvents();
                    });
                } else {
                    Swal.fire({
                        title: 'Sync Failed',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to sync events. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        }
    });
}
