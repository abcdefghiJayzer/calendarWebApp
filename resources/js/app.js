import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');

    if (calendarEl) {
        var eventsUrl = calendarEl.getAttribute('data-events-url');

        var calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            events: eventsUrl,

            eventClick: function (info) {
                let eventId = info.event.id;
                let baseUrl = window.location.origin + "/OJT/calendarWebApp"; // Adjust base URL
                let eventDetailsUrl = `${baseUrl}/events/${eventId}`;

                console.log("Redirecting to:", eventDetailsUrl); // Debugging

                fetch(eventDetailsUrl)
                    .then(response => response.json())
                    .then(data => console.log("Event Data:", data))
                    .catch(error => console.error("Error fetching event:", error));

                window.location.href = eventDetailsUrl; // Redirect to JSON details
            }


        });

        calendar.render();
    } else {
        console.error('Calendar element not found.');
    }
});
