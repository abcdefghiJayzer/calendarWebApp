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
            }],
            eventDidMount: function(info) {
                // Apply filter settings when events are mounted
                const savedFilters = localStorage.getItem('calendarFilters')
                    ? JSON.parse(localStorage.getItem('calendarFilters'))
                    : ['institute', 'sectoral', 'division'];

                const shouldShow = savedFilters.includes(info.event.extendedProps.calendarType);
                info.event.setProp('display', shouldShow ? 'auto' : 'none');
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
                        description: info.event.extendedProps.description || '',  // Changed from 'Google Calendar Event' to empty string
                        location: info.event.extendedProps.location || '',
                        guests: info.event.extendedProps.guests || []
                    }
                };

                openEventModal(eventData);
            }
        });

        window.calendar = calendar; // Make calendar globally accessible
        calendar.render();

        // Add resize observer to handle container width changes
        const resizeObserver = new ResizeObserver(() => {
            calendar.updateSize();
        });

        resizeObserver.observe(document.getElementById('calendar-container'));
    } else {
        console.error('Calendar element not found.');
    }
});
