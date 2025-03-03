import './bootstrap';
import { Calendar } from '@fullcalendar/core';
import interactionPlugin from '@fullcalendar/interaction';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import multiMonthPlugin from '@fullcalendar/multimonth';


document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');

    if (calendarEl) {
        var calendar = new Calendar(calendarEl, {
            plugins: [interactionPlugin, dayGridPlugin, timeGridPlugin, listPlugin, multiMonthPlugin],
            initialView: 'dayGridMonth', // Default view
            selectable: true, // Enable date selection
            editable: true, // Enable event dragging & resizing
        });

        calendar.render();
    }
});
