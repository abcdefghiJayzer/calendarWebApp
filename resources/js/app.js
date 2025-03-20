import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import googleCalendarPlugin from '@fullcalendar/google-calendar';

document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');

    if (calendarEl) {
        var eventsUrl = calendarEl.getAttribute('data-events-url');

        var calendar = new Calendar(calendarEl, {
            plugins: [
                dayGridPlugin,
                timeGridPlugin,
                listPlugin,
                interactionPlugin,
                googleCalendarPlugin
            ],
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            events: eventsUrl,
            eventClick: function (info) {
                if (info.event.url) {
                    window.open(info.event.url, '_blank'); // Opens Google Calendar event links
                    info.jsEvent.preventDefault();
                } else {
                    fetch(`/OJT/calendarWebApp/events/${info.event.id}`)
                        .then(response => response.json())
                        .then(data => {
                            openEventModal(data);
                        })
                        .catch(error => console.error('Error:', error));
                }
            }
        });

        calendar.render();
    } else {
        console.error('Calendar element not found.');
    }
});
