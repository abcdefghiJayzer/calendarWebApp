import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import googleCalendarPlugin from '@fullcalendar/google-calendar';
import Swal from 'sweetalert2';

window.Swal = Swal; // Make it available globally
window.baseUrl = '/OJT/calendarWebApp'; // Define base URL for consistent usage

document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');

    if (calendarEl) {
        // Log the Google Calendar configuration for debugging
        console.log('Google Calendar config:', {
            apiKey: calendarEl.getAttribute('data-api-key'),
            calendarId: calendarEl.getAttribute('data-calendar-id'),
            isAuthenticated: calendarEl.getAttribute('data-is-authenticated') === 'true'
        });

        const isGoogleAuthenticated = calendarEl.getAttribute('data-is-authenticated') === 'true';

        try {
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
                dateClick: function (info) {
                    const startDate = new Date(info.date);
                    const endDate = new Date(info.date);
                    endDate.setHours(startDate.getHours() + 1); // Set end date 1 hour after start
                    if (typeof openModal === 'function') {
                        openModal(startDate, endDate);
                    } else {
                        console.error("openModal function is not defined");
                    }
                },
                eventSources: [
                    // Local events source
                    {
                        url: calendarEl.getAttribute('data-events-url'),
                        failure: function (error) {
                            console.error('Failed to load local events:', error);
                        }
                    }
                ],
                eventDidMount: function (info) {
                    try {
                        // Mark Google Calendar events with the institute calendarType
                        if (info.event.source && info.event.source.url === undefined && info.event.extendedProps.source !== 'local') {
                            // Check if this is from Google Calendar based on the ID format (Google events have @ in their ID)
                            const isGoogleEvent = info.event.id.includes('@');

                            if (isGoogleEvent) {
                                // Prefix Google event IDs with 'google_' for easier identification
                                info.event.setProp('id', 'google_' + info.event.id);

                                // For Google Calendar events, extract details from the Google event
                                const location = info.event.extendedProps.location || '';
                                const description = info.event.extendedProps.description || '';

                                // Ensure the extendedProps has all necessary fields
                                info.event.setExtendedProps({
                                    calendarType: 'institute', // Set Google Calendar events to institute type
                                    source: 'google',
                                    editable: isGoogleAuthenticated, // Only editable if user is authenticated
                                    description: description,
                                    location: location,
                                    guests: info.event.extendedProps.attendees ?
                                        info.event.extendedProps.attendees.map(a => a.email) : [],
                                    private: false
                                });

                                console.log('Processed Google event:', info.event.title, info.event.id, info.event.extendedProps);
                            }
                        }

                        // Apply filter settings
                        const savedFilters = localStorage.getItem('calendarFilters')
                            ? JSON.parse(localStorage.getItem('calendarFilters'))
                            : ['institute', 'sectoral', 'division'];

                        // For Google events, use 'institute', for local events use the defined type
                        const calendarType = info.event.extendedProps.source === 'google' ?
                            'institute' : info.event.extendedProps.calendarType || 'division';

                        const shouldShow = calendarType && savedFilters.includes(calendarType);
                        info.event.setProp('display', shouldShow ? 'auto' : 'none');
                    } catch (err) {
                        console.error('Error in eventDidMount:', err);
                    }
                },
                eventClick: function (info) {
                    info.jsEvent.preventDefault(); // Prevent default for all events
                    try {
                        // Create event data object in the format expected by openEventModal
                        const eventData = {
                            id: info.event.id,
                            title: info.event.title,
                            start: info.event.start,
                            end: info.event.end,
                            allDay: info.event.allDay,
                            backgroundColor: info.event.backgroundColor,
                            extendedProps: {
                                description: info.event.extendedProps.description || '',
                                location: info.event.extendedProps.location || '',
                                guests: info.event.extendedProps.guests || [],
                                source: info.event.extendedProps.source || 'local',
                                calendarType: info.event.extendedProps.calendarType || 'institute',
                                editable: info.event.extendedProps.editable !== undefined ?
                                    info.event.extendedProps.editable : true
                            }
                        };

                        if (typeof openEventModal === 'function') {
                            openEventModal(eventData);
                        } else {
                            console.error("openEventModal function is not defined");
                        }
                    } catch (err) {
                        console.error('Error in eventClick:', err);
                    }
                }
            });

            // Add Google Calendar source only if API key and Calendar ID exist
            const apiKey = calendarEl.getAttribute('data-api-key');
            const calendarId = calendarEl.getAttribute('data-calendar-id');

            if (apiKey && calendarId) {
                console.log('Adding Google Calendar source', { apiKey, calendarId });
                calendar.addEventSource({
                    googleCalendarId: calendarId,
                    googleCalendarApiKey: apiKey,
                    className: 'gcal-event',
                    color: '#0288d1',
                    textColor: 'white',
                    success: function (events) {
                        console.log('Google Calendar events loaded successfully:', events.length);
                    },
                    failure: function (error) {
                        console.error('Failed to load Google Calendar events:', error);
                    }
                });
            } else {
                console.warn('Missing Google Calendar API key or Calendar ID');
            }

            window.calendar = calendar; // Make calendar globally accessible
            calendar.render();

            // Add resize observer to handle container width changes
            const calendarContainer = document.getElementById('calendar-container');
            if (calendarContainer) {
                const resizeObserver = new ResizeObserver(() => {
                    calendar.updateSize();
                });

                resizeObserver.observe(calendarContainer);
            } else {
                console.warn('Element with id "calendar-container" not found. ResizeObserver not initialized.');
            }

            console.log('Calendar initialized successfully');
        } catch (error) {
            console.error('Error initializing calendar:', error);
        }
    } else {
        console.error('Calendar element not found.');
    }

    // Extend the global window object to include our Google Calendar functions
    window.googleCalendar = {
        createEvent: async function (formData) {
            try {
                const response = await fetch(`${window.baseUrl}/google/events`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to create Google Calendar event');
                }

                return await response.json();
            } catch (error) {
                console.error('Error creating Google Calendar event:', error);
                throw error;
            }
        },

        updateEvent: async function (eventId, formData) {
            try {
                console.log(`Sending update to ${window.baseUrl}/google/events/${eventId}`);

                // Debug form data before sending
                console.log("Form data being sent for Google Calendar update:");
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                const response = await fetch(`${window.baseUrl}/google/events/${eventId}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Server response:', errorText);
                    throw new Error('Failed to update Google Calendar event');
                }

                return await response.json();
            } catch (error) {
                console.error('Error updating Google Calendar event:', error);
                throw error;
            }
        },

        deleteEvent: async function (eventId) {
            try {
                // Remove 'google_' prefix before sending to the server
                const googleEventId = eventId.replace('google_', '');
                const response = await fetch(`${window.baseUrl}/google/events/${googleEventId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to delete Google Calendar event');
                }

                return await response.json();
            } catch (error) {
                console.error('Error deleting Google Calendar event:', error);
                throw error;
            }
        }
    };
});
