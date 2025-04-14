<aside class="fixed top-0 left-0 h-full w-64 bg-green-800 border-r">
    <div class="flex flex-col h-full">
        <div class="flex items-center justify-center h-16">
            <a href="/" class="text-xl font-semibold text-white">
                Calendar
            </a>
        </div>

        <div class="flex-grow p-4 space-y-2">
            <button onclick="openModal()"
                class="block w-full py-2 px-4 text-left text-white bg-green-900 rounded-lg hover:bg-green-700">
                Create Event
            </button>

            <a href="{{ route('home') }}"
                class="block py-2 px-4 text-white bg-green-900 rounded-lg hover:text-white">
                Home
            </a>

            <!-- Google Calendar Connection Section -->
            <div class="mt-4 p-3 bg-green-700 rounded-lg">
                <h3 class="text-white font-medium mb-2">Google Calendar</h3>
                <div id="sidebar-google-status" class="text-gray-300 text-sm mb-2">Checking status...</div>
                <div id="sidebar-google-account" class="text-xs text-gray-300 mb-2 italic hidden"></div>
                <div class="flex flex-col space-y-2">
                    <button id="sidebar-connect-btn" onclick="window.location.href='{{ route('google.auth') }}'"
                        class="py-1 px-3 bg-green-600 text-white text-sm rounded hover:bg-green-500">
                        Connect
                    </button>
                    <button id="sidebar-disconnect-btn" onclick="disconnectGoogle()"
                        class="py-1 px-3 bg-red-600 text-white text-sm rounded hover:bg-red-500 hidden">
                        Disconnect
                    </button>
                </div>
            </div>

            <div class="mt-8">
                <h3 class="text-white font-medium mb-2">Calendar Filters</h3>
                <div class="space-y-2">
                    <label class="flex items-center text-white cursor-pointer">
                        <input type="checkbox" class="calendar-filter form-checkbox text-green-500 rounded" value="institute">
                        <span class="ml-2">Institute Level</span>
                    </label>
                    <label class="flex items-center text-white cursor-pointer">
                        <input type="checkbox" class="calendar-filter form-checkbox text-green-500 rounded" value="sectoral">
                        <span class="ml-2">Sectoral</span>
                    </label>
                    <label class="flex items-center text-white cursor-pointer">
                        <input type="checkbox" class="calendar-filter form-checkbox text-green-500 rounded" value="division">
                        <span class="ml-2">Division</span>
                    </label>
                    <label class="flex items-center text-white cursor-pointer">
                        <input type="checkbox" class="calendar-filter form-checkbox text-green-500 rounded" value="google">
                        <span class="ml-2">Google Calendar</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing sidebar filters');
    const calendarFilters = document.querySelectorAll('.calendar-filter');

    try {
        // Load saved filter preferences
        const savedFilters = localStorage.getItem('calendarFilters')
            ? JSON.parse(localStorage.getItem('calendarFilters'))
            : ['institute', 'sectoral', 'division', 'google']; // Default all checked including Google

        console.log('Saved filters from localStorage:', savedFilters);

        // Apply saved preferences to checkboxes
        calendarFilters.forEach(filter => {
            filter.checked = savedFilters.includes(filter.value);
            console.log(`Filter ${filter.value} checked:`, filter.checked);
        });

        function updateCalendarEvents() {
            const selectedCalendars = Array.from(calendarFilters)
                .filter(cb => cb.checked)
                .map(cb => cb.value);

            console.log('Selected calendar filters:', selectedCalendars);

            // Save filter preferences
            localStorage.setItem('calendarFilters', JSON.stringify(selectedCalendars));

            if (window.calendar) {
                const allEvents = window.calendar.getEvents();
                console.log(`Applying filters to ${allEvents.length} events`);

                // Handle visibility for all existing events
                allEvents.forEach(event => {
                    // Handle Google events differently
                    const isGoogleEvent = event.extendedProps.source === 'google' ||
                                         event.id.startsWith('google_');

                    // Determine calendar type - special handling for Google events
                    let calendarType;
                    if (isGoogleEvent) {
                        calendarType = 'google';
                    } else {
                        calendarType = event.extendedProps.calendarType || 'division';
                    }

                    const shouldShow = selectedCalendars.includes(calendarType);
                    console.log(`Event "${event.title}" (${calendarType}): ${shouldShow ? 'show' : 'hide'}`);
                    event.setProp('display', shouldShow ? 'auto' : 'none');
                });

                // Special handling for Google Calendar events - add/remove Google source based on filter
                const googleFilterActive = selectedCalendars.includes('google');
                const isGoogleAuthenticated = document.getElementById('calendar')?.getAttribute('data-is-authenticated') === 'true';

                // Check for existing Google Calendar source in a more reliable way
                const hasGoogleSource = window.calendar.getEventSourceById('google-calendar-source') ||
                    window.calendar.getEventSources().some(source => source.url && source.url.includes('google'));

                if (googleFilterActive && isGoogleAuthenticated && !hasGoogleSource) {
                    // Use the new toggleGoogleEvents function for more reliable handling
                    if (typeof window.googleCalendar?.toggleGoogleEvents === 'function') {
                        window.googleCalendar.toggleGoogleEvents(true);
                    } else {
                        // Fallback to previous method
                        console.log('Adding Google Calendar source due to filter activation');
                        setTimeout(() => window.refreshGoogleEvents(), 100);
                    }
                } else if (!googleFilterActive && hasGoogleSource) {
                    // Need to remove Google source but keep the Google filter state
                    console.log('Removing Google Calendar source due to filter deactivation');

                    // Use the toggle function if available
                    if (typeof window.googleCalendar?.toggleGoogleEvents === 'function') {
                        window.googleCalendar.toggleGoogleEvents(false);
                    } else {
                        // Remove by ID if it exists
                        const googleSource = window.calendar.getEventSourceById('google-calendar-source');
                        if (googleSource) {
                            googleSource.remove();
                        }

                        // Also check by URL
                        window.calendar.getEventSources().forEach(source => {
                            if (source.url && source.url.includes('google')) {
                                source.remove();
                            }
                        });
                    }
                }
            } else {
                console.warn('Calendar not initialized yet, cannot apply filters');
            }
        }

        calendarFilters.forEach(filter => {
            filter.addEventListener('change', function() {
                console.log(`Filter ${this.value} changed to ${this.checked}`);

                // Special handling for Google Calendar filter
                if (this.value === 'google') {
                    const isGoogleAuthenticated = document.getElementById('calendar')?.getAttribute('data-is-authenticated') === 'true';

                    // Use our dedicated function for toggling Google events
                    if (isGoogleAuthenticated && typeof window.googleCalendar?.toggleGoogleEvents === 'function') {
                        window.googleCalendar.toggleGoogleEvents(this.checked);
                    }
                }

                // Continue with normal filter handling
                updateCalendarEvents();
            });
        });

        // Apply filters on initial load and when window.calendar becomes available
        const checkCalendarReady = setInterval(() => {
            if (window.calendar) {
                console.log('Calendar is ready, applying filters');
                updateCalendarEvents();
                clearInterval(checkCalendarReady);
            } else {
                console.log('Waiting for calendar to initialize...');
            }
        }, 500);

        // Clear the interval after 10 seconds to prevent infinite checking
        setTimeout(() => clearInterval(checkCalendarReady), 10000);

        // Check Google auth status
        updateGoogleAuthStatus();

    } catch (error) {
        console.error('Error in sidebar filter initialization:', error);
    }
});

function disconnectGoogle() {
    fetch('{{ route("google.disconnect") }}')
        .then(response => response.json())
        .then(data => {
            if (data.forceRefresh) {
                // Use our new refresh function if available
                if (typeof window.refreshGoogleEvents === 'function') {
                    // Clear connected account from storage first
                    sessionStorage.removeItem('connected_google_account');

                    // Then thoroughly clean all Google events
                    window.clearAllGoogleEvents();

                    // Uncheck the Google Calendar filter checkbox
                    const googleCheckbox = document.querySelector('.calendar-filter[value="google"]');
                    if (googleCheckbox) {
                        googleCheckbox.checked = false;

                        // Update the saved filters in localStorage
                        let savedFilters = localStorage.getItem('calendarFilters')
                            ? JSON.parse(localStorage.getItem('calendarFilters'))
                            : [];

                        // Remove 'google' from the saved filters
                        savedFilters = savedFilters.filter(f => f !== 'google');
                        localStorage.setItem('calendarFilters', JSON.stringify(savedFilters));

                        console.log('Google Calendar filter unchecked and removed from saved filters');
                    }

                    // Finally update UI
                    updateGoogleAuthStatus();

                    Swal.fire({
                        icon: 'success',
                        title: 'Disconnected',
                        text: 'Successfully disconnected from Google Calendar.',
                        confirmButtonColor: '#22c55e'
                    });
                } else {
                    // Fall back to full page reload if needed
                    window.location.reload(true);
                }
            } else {
                updateGoogleAuthStatus();
            }
        })
        .catch(error => {
            console.error('Error disconnecting from Google:', error);
            updateGoogleAuthStatus();
        });
}

function updateGoogleAuthStatus() {
    fetch('{{ route("google.status") }}')
        .then(response => response.json())
        .then(data => {
            const statusEl = document.getElementById('sidebar-google-status');
            const connectBtn = document.getElementById('sidebar-connect-btn');
            const disconnectBtn = document.getElementById('sidebar-disconnect-btn');
            const accountEl = document.getElementById('sidebar-google-account');

            if (data.authenticated) {
                statusEl.textContent = 'Connected ✓';
                statusEl.classList.add('text-green-300');
                connectBtn.classList.add('hidden');
                disconnectBtn.classList.remove('hidden');

                if (data.email) {
                    accountEl.textContent = `Account: ${data.email}`;
                    accountEl.classList.remove('hidden');

                    // Store current account email in sessionStorage for change detection
                    const previousAccount = sessionStorage.getItem('connected_google_account');
                    if (previousAccount && previousAccount !== data.email) {
                        console.log('Google account changed:', previousAccount, '->', data.email);

                        // Force refresh of Google Calendar events when account changes
                        if (typeof window.refreshGoogleEvents === 'function') {
                            window.refreshGoogleEvents();

                            Swal.fire({
                                icon: 'success',
                                title: 'Google Account Changed',
                                text: `Switched from ${previousAccount} to ${data.email}. Calendar events have been updated.`,
                                confirmButtonColor: '#22c55e'
                            });
                        }
                    }
                    sessionStorage.setItem('connected_google_account', data.email);
                }
            } else {
                statusEl.textContent = 'Not Connected';
                statusEl.classList.remove('text-green-300');
                connectBtn.classList.remove('hidden');
                disconnectBtn.classList.add('hidden');
                accountEl.classList.add('hidden');
                sessionStorage.removeItem('connected_google_account');
            }
        })
        .catch(error => console.error('Error checking Google auth status:', error));
}

document.addEventListener('DOMContentLoaded', updateGoogleAuthStatus);
</script>
