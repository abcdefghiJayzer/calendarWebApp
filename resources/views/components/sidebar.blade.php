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
    const calendarFilters = document.querySelectorAll('.calendar-filter');

    // Load saved filter preferences
    const savedFilters = localStorage.getItem('calendarFilters')
        ? JSON.parse(localStorage.getItem('calendarFilters'))
        : ['institute', 'sectoral', 'division']; // Default all checked

    // Apply saved preferences to checkboxes
    calendarFilters.forEach(filter => {
        filter.checked = savedFilters.includes(filter.value);
    });

    function updateCalendarEvents() {
        const selectedCalendars = Array.from(calendarFilters)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        // Save filter preferences
        localStorage.setItem('calendarFilters', JSON.stringify(selectedCalendars));

        if (window.calendar) {
            window.calendar.getEvents().forEach(event => {
                const shouldShow = selectedCalendars.includes(event.extendedProps.calendarType);
                event.setProp('display', shouldShow ? 'auto' : 'none');
            });
        }
    }

    calendarFilters.forEach(filter => {
        filter.addEventListener('change', updateCalendarEvents);
    });

    // Apply filters on initial load
    updateCalendarEvents();
});
</script>
