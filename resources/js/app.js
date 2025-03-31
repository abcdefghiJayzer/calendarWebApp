import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import googleCalendarPlugin from '@fullcalendar/google-calendar';
import Swal from 'sweetalert2';

window.Swal = Swal; // Make it available globally

document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');

    if (calendarEl) {
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
            events: calendarEl.getAttribute('data-events-url'),
            googleCalendarApiKey: calendarEl.getAttribute('data-api-key'),
            eventSources: [{
                googleCalendarId: calendarEl.getAttribute('data-calendar-id'),
                className: 'gcal-event'
            }],
            eventDidMount: function(info) {
                info.el.style.cursor = 'pointer';
            },
            eventClick: function (info) {
                info.jsEvent.preventDefault(); // Prevent default for all events

                // Create event data object in the format expected by openEventModal
                const eventData = {
                    id: info.event.id,
                    title: info.event.title,
                    start: info.event.start,
                    end: info.event.end,
                    allDay: info.event.allDay,
                    extendedProps: {
                        description: info.event.extendedProps.description || 'Google Calendar Event',
                        location: info.event.extendedProps.location || '',
                        guests: info.event.extendedProps.guests || []
                    }
                };

                openEventModal(eventData);
            }
        });

        calendar.render();

        // Make calendar instance globally available
        window.calendar = calendar;

        // Add resize observer to handle container width changes
        const resizeObserver = new ResizeObserver(() => {
            calendar.updateSize();
        });

        resizeObserver.observe(document.getElementById('calendar-container'));
    } else {
        console.error('Calendar element not found.');
    }
});
