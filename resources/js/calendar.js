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
