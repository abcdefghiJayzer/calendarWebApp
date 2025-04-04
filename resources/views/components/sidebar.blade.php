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
                <div id="sidebar-google-status" class="text-sm text-white mb-2">
                    Checking connection...
                </div>
                <div id="sidebar-connect-btn" class="hidden">
                    <a href="{{ route('google.auth') }}" class="block w-full py-2 px-4 text-center text-white bg-green-900 rounded-lg hover:bg-green-600">
                        Connect
                    </a>
                </div>
                <div id="sidebar-disconnect-btn" class="hidden">
                    <a href="{{ route('google.disconnect') }}" class="block w-full py-2 px-4 text-center text-white bg-red-600 rounded-lg hover:bg-red-700">
                        Disconnect
                    </a>
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
            : ['institute', 'sectoral', 'division']; // Default all checked

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

                allEvents.forEach(event => {
                    // Get the calendar type from event properties
                    const calendarType = event.extendedProps.calendarType || 'division';
                    const shouldShow = selectedCalendars.includes(calendarType);
                    console.log(`Event "${event.title}" (${calendarType}): ${shouldShow ? 'show' : 'hide'}`);
                    event.setProp('display', shouldShow ? 'auto' : 'none');
                });
            } else {
                console.warn('Calendar not initialized yet, cannot apply filters');
            }
        }

        calendarFilters.forEach(filter => {
            filter.addEventListener('change', function() {
                console.log(`Filter ${this.value} changed to ${this.checked}`);
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
        fetch('{{ route("google.status") }}')
            .then(response => response.json())
            .then(data => {
                console.log('Google auth status in sidebar:', data);
                const statusEl = document.getElementById('sidebar-google-status');
                const connectBtn = document.getElementById('sidebar-connect-btn');
                const disconnectBtn = document.getElementById('sidebar-disconnect-btn');

                if (data.authenticated) {
                    statusEl.textContent = 'Connected ✓';
                    statusEl.classList.add('text-green-300');
                    disconnectBtn.classList.remove('hidden');
                } else {
                    statusEl.textContent = 'Not connected';
                    statusEl.classList.add('text-red-300');
                    connectBtn.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error checking Google auth status:', error);
                document.getElementById('sidebar-google-status').textContent = 'Connection error';
                document.getElementById('sidebar-google-status').classList.add('text-red-300');
                document.getElementById('sidebar-connect-btn').classList.remove('hidden');
            });

    } catch (error) {
        console.error('Error in sidebar filter initialization:', error);
    }
});
</script>
