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
                                    calendarType: info.event.extendedProps.calendarType || 'institute', // Set Google Calendar events to institute type by default but allow override if specified
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

                        // Check if this is an admin user - ensure we get the actual user data from document or a data attribute
                        const userDivision = document.body.getAttribute('data-user-division') || '';
                        const isAdmin = userDivision === 'institute';

                        // Apply filter settings
                        const savedFilters = localStorage.getItem('calendarFilters')
                            ? JSON.parse(localStorage.getItem('calendarFilters'))
                            : isAdmin
                                ? [
                                    'institute',
                                    'sector1', 'sector2', 'sector3', 'sector4',
                                    'sector1_div1', 'sector2_div1', 'sector3_div1', 'sector4_div1',
                                    'google'
                                ]
                                : ['institute', 'google'];

                        // Check both the source property AND if the ID starts with 'google_'
                        const isGoogleEvent = info.event.extendedProps.source === 'google' ||
                            info.event.id.startsWith('google_');

                        // Get calendar type from extendedProps or fallback
                        const calendarType =
                            isGoogleEvent ? 'google'
                            : info.event.extendedProps.calendarType
                                || info.event.extendedProps.calendar_type
                                || info.event.extendedProps.calendar_type
                                || info.event.calendar_type
                                || 'institute';

                        // Show events based on selected filters
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

            // Get Google account email from sessionStorage to use as cache breaker
            const connectedAccount = sessionStorage.getItem('connected_google_account') || '';

            // Check if Google Calendar filter is active before loading
            const savedFilters = localStorage.getItem('calendarFilters')
                ? JSON.parse(localStorage.getItem('calendarFilters'))
                : ['institute', 'sectoral', 'division', 'google'];

            // Only load Google Calendar source if authenticated AND the filter is active
            if (apiKey && calendarId && isGoogleAuthenticated && savedFilters.includes('google')) {
                // First check if we already have a Google Calendar source to prevent duplicates
                const hasExistingGoogleSource = calendar.getEventSources().some(source =>
                    source.url && source.url.includes('google'));

                if (!hasExistingGoogleSource) {
                    console.log('Adding Google Calendar source during initialization', { apiKey, calendarId, connectedAccount });

                    // Add the Google Calendar source with a unique ID
                    calendar.addEventSource({
                        id: 'google-calendar-source',
                        googleCalendarId: calendarId,
                        googleCalendarApiKey: apiKey,
                        className: 'gcal-event',
                        color: '#0288d1',
                        textColor: 'white',
                        cache: false,
                        extraParams: {
                            account: connectedAccount,
                            _: new Date().getTime() // Add timestamp as query param
                        },
                        eventDataTransform: function(eventData) {
                            // Ensure Google events are immediately tagged correctly
                            if (!eventData.extendedProps) {
                                eventData.extendedProps = {};
                            }
                            eventData.extendedProps.source = 'google';
                            eventData.extendedProps.googleAccount = connectedAccount;
                            return eventData;
                        },
                        success: function (events) {
                            console.log(`Google Calendar events loaded successfully for ${connectedAccount}:`, events.length);

                            // Force calendar to re-render all events to ensure they display correctly
                            setTimeout(() => {
                                window.calendar.render();
                            }, 100);
                        },
                        failure: function (error) {
                            console.error('Failed to load Google Calendar events:', error);
                        }
                    });
                } else {
                    console.log('Google Calendar source already exists, skipping duplicate addition');
                }
            } else {
                if (!isGoogleAuthenticated) {
                    console.warn('Not authenticated with Google Calendar, events not loaded');
                } else if (!savedFilters.includes('google')) {
                    console.warn('Google Calendar filter is off, events not loaded');
                } else {
                    console.warn('Missing Google Calendar API key or Calendar ID');
                }
            }

            window.calendar = calendar; // Make calendar globally accessible
            calendar.render();

            // Debug: Log all events loaded and their calendar types
            window.logEventTypes = function() {
                const events = calendar.getEvents();
                console.log(`Found ${events.length} events in calendar`);
                events.forEach(event => {
                    console.log(`Event "${event.title}" - Type: ${event.extendedProps.calendarType || 'unknown'}, Visible: ${event.display !== 'none'}`);
                });

                // Show active filters
                const savedFilters = localStorage.getItem('calendarFilters')
                    ? JSON.parse(localStorage.getItem('calendarFilters'))
                    : [];
                console.log('Active filters:', savedFilters);
            };

            // Run the debug function after events are loaded
            setTimeout(() => {
                window.logEventTypes();
            }, 1000);

            // Add resize observer to handle container width changes
            const calendarContainer = document.getElementById('calendar-container');
            if (calendarContainer) {
                const resizeObserver = new ResizeObserver(entries => {
                    calendar.updateSize();
                });

                resizeObserver.observe(calendarContainer);
            }

            // Properly define Google Calendar API utilities
            window.googleCalendar = {
                createEvent: async function(formData) {
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

                updateEvent: async function(eventId, formData) {
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

                deleteEvent: async function(eventId) {
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
                },

                // Add a new function to dynamically toggle Google Calendar visibility
                toggleGoogleEvents: function(enable) {
                    console.log(`Toggle Google Calendar events: ${enable ? 'ON' : 'OFF'}`);

                    // First, remove any existing Google sources
                    const existingSources = window.calendar.getEventSources().filter(source =>
                        source.url && source.url.includes('google') || source.id === 'google-calendar-source'
                    );

                    existingSources.forEach(source => source.remove());

                    // Add events only if enabled
                    if (enable) {
                        const apiKey = document.getElementById('calendar').getAttribute('data-api-key');
                        const calendarId = document.getElementById('calendar').getAttribute('data-calendar-id');
                        const isAuthenticated = document.getElementById('calendar').getAttribute('data-is-authenticated') === 'true';
                        const connectedAccount = sessionStorage.getItem('connected_google_account') || '';

                        if (apiKey && calendarId && isAuthenticated) {
                            window.calendar.addEventSource({
                                id: 'google-calendar-source',
                                googleCalendarId: calendarId,
                                googleCalendarApiKey: apiKey,
                                className: 'gcal-event',
                                color: '#0288d1',
                                textColor: 'white',
                                cache: false,
                                extraParams: {
                                    account: connectedAccount,
                                    _: new Date().getTime() // Cache-busting timestamp
                                },
                                eventDataTransform: function(eventData) {
                                    if (!eventData.extendedProps) {
                                        eventData.extendedProps = {};
                                    }
                                    eventData.extendedProps.source = 'google';
                                    return eventData;
                                }
                            });

                            // Force refresh to ensure events appear immediately
                            window.calendar.refetchEvents();
                        }
                    }
                }
            };

            // Utility function to refresh Google events - accessible globally
            window.refreshGoogleEvents = function() {
                if (window.calendar) {
                    console.log('Forcing refresh of Google Calendar events');

                    // Check if Google Calendar filter is active
                    const savedFilters = localStorage.getItem('calendarFilters')
                        ? JSON.parse(localStorage.getItem('calendarFilters'))
                        : ['institute', 'sectoral', 'division', 'google'];

                    const googleFilterActive = savedFilters.includes('google');

                    // Remove all Google event sources first by finding sources with google in URL
                    const googleSources = window.calendar.getEventSources().filter(source =>
                        source.url && source.url.includes('google')
                    );

                    if (googleSources.length > 0) {
                        console.log(`Removing ${googleSources.length} existing Google Calendar sources`);
                        googleSources.forEach(source => source.remove());
                    }

                    // Also explicitly remove our uniquely identified source if it exists
                    const googleSourceById = window.calendar.getEventSourceById('google-calendar-source');
                    if (googleSourceById) {
                        console.log('Removing Google Calendar source by ID');
                        googleSourceById.remove();
                    }

                    // Then remove any remaining Google events that might be lingering
                    const googleEvents = window.calendar.getEvents().filter(event =>
                        event.id.startsWith('google_') ||
                        (event.extendedProps && event.extendedProps.source === 'google')
                    );

                    if (googleEvents.length > 0) {
                        console.log(`Found ${googleEvents.length} Google events to remove`);
                        googleEvents.forEach(event => event.remove());
                    }

                    // Get current Google account
                    const connectedAccount = sessionStorage.getItem('connected_google_account') || '';
                    const apiKey = document.getElementById('calendar').getAttribute('data-api-key');
                    const calendarId = document.getElementById('calendar').getAttribute('data-calendar-id');
                    const isGoogleAuthenticated = document.getElementById('calendar').getAttribute('data-is-authenticated') === 'true';

                    // ONLY add event source back if filter is active AND authenticated
                    if (apiKey && calendarId && connectedAccount && isGoogleAuthenticated && googleFilterActive) {
                        console.log('Adding Google Calendar source with filter check:', { googleFilterActive });

                        // Add a short delay before adding the source to ensure removal is complete
                        setTimeout(() => {
                            window.calendar.addEventSource({
                                id: 'google-calendar-source',
                                googleCalendarId: calendarId,
                                googleCalendarApiKey: apiKey,
                                className: 'gcal-event',
                                color: '#0288d1',
                                textColor: 'white',
                                cache: false,
                                extraParams: {
                                    account: connectedAccount,
                                    _: new Date().getTime()  // Cache-busting
                                },
                                eventDataTransform: function(eventData) {
                                    if (!eventData.extendedProps) {
                                        eventData.extendedProps = {};
                                    }
                                    eventData.extendedProps.source = 'google';
                                    eventData.extendedProps.googleAccount = connectedAccount;
                                    return eventData;
                                }
                            });
                        }, 300);
                    } else {
                        if (!isGoogleAuthenticated) {
                            console.log('Not authenticated with Google Calendar, events not reloaded');
                        } else if (!googleFilterActive) {
                            console.log('Google Calendar filter is off, events not reloaded');
                        }
                    }

                    // Force refetch of all events
                    window.calendar.refetchEvents();
                } else {
                    console.warn('Calendar not initialized, cannot refresh Google events');
                }
            };

            // New function to completely clear Google events without re-adding them
            window.clearAllGoogleEvents = function() {
                if (window.calendar) {
                    console.log('Clearing all Google Calendar events');

                    // Remove specifically by ID if it exists
                    const googleSource = window.calendar.getEventSourceById('google-calendar-source');
                    if (googleSource) {
                        console.log('Removing Google Calendar source by ID');
                        googleSource.remove();
                    }

                    // Remove all Google Calendar sources that match URL pattern
                    const googleSources = window.calendar.getEventSources().filter(source =>
                        source.url && source.url.includes('google')
                    );

                    if (googleSources.length > 0) {
                        console.log(`Removing ${googleSources.length} Google Calendar sources by URL`);
                        googleSources.forEach(source => source.remove());
                    }

                    // Then also remove any Google events that might still be in the calendar
                    const googleEvents = window.calendar.getEvents().filter(event =>
                        event.id.startsWith('google_') ||
                        (event.extendedProps && event.extendedProps.source === 'google')
                    );

                    if (googleEvents.length > 0) {
                        console.log(`Found ${googleEvents.length} Google events to remove`);
                        googleEvents.forEach(event => event.remove());
                    }

                    // Update calendar display
                    window.calendar.render();

                    // Set Google authentication attribute to false on calendar element
                    const calendarEl = document.getElementById('calendar');
                    if (calendarEl) {
                        calendarEl.setAttribute('data-is-authenticated', 'false');
                        console.log('Set calendar Google authentication status to false');
                    }
                } else {
                    console.warn('Calendar not initialized, cannot clear Google events');
                }
            };

            // Add global helper function for refreshing calendar events
            window.refreshCalendarEvents = function() {
                console.log('Global calendar refresh called');
                if (window.calendar && typeof window.calendar.refetchEvents === 'function') {
                    window.calendar.refetchEvents();
                    return true;
                } else {
                    console.warn('Calendar not available for refresh');
                    return false;
                }
            };

            // Store reference to calendar in DOM element for backup access
            calendarEl._calendar = window.calendar;
        } catch (error) {
            console.error('Error initializing calendar:', error);
        }
    }

    // Add sync all to Google Calendar functionality
    const syncAllBtn = document.getElementById('sync-all-to-google');
    if (syncAllBtn) {
        syncAllBtn.addEventListener('click', async function() {
            // Check if Google is authenticated
            const calendarEl = document.getElementById('calendar');
            const isAuthenticated = calendarEl && calendarEl.getAttribute('data-is-authenticated') === 'true';

            if (!isAuthenticated) {
                Swal.fire({
                    title: 'Google Authentication Required',
                    text: 'You need to connect your Google account first',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#22c55e',
                    confirmButtonText: 'Connect Google Account',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `${window.baseUrl}/google/auth`;
                    }
                });
                return;
            }

            // Show loading state
            syncAllBtn.disabled = true;
            syncAllBtn.innerHTML = `
                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Syncing...
            `;

            try {
                const response = await fetch(`${window.baseUrl}/sync-all-to-google`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const result = await response.json();

                if (response.ok) {
                    // If there's a redirect URL, user needs to authenticate
                    if (result.redirect) {
                        Swal.fire({
                            title: 'Authentication Required',
                            text: result.message || 'You need to authenticate with Google Calendar',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#22c55e',
                            confirmButtonText: 'Connect Now',
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = result.redirect;
                            }
                        });
                    } else {
                        // Success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: result.message || 'Events synced successfully!',
                            confirmButtonColor: '#22c55e'
                        }).then(() => {
                            if (window.calendar && typeof window.calendar.refetchEvents === 'function') {
                                window.calendar.refetchEvents();
                            }
                        });
                    }
                } else {
                    throw new Error(result.message || 'Failed to sync events');
                }
            } catch (error) {
                console.error('Error syncing events to Google Calendar:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Sync Failed',
                    text: error.message || 'Failed to sync events. Please try again.',
                    confirmButtonColor: '#22c55e'
                });
            } finally {
                // Restore button state
                syncAllBtn.disabled = false;
                syncAllBtn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                    Sync All Events to Google
                `;
            }
        });
    }
});
