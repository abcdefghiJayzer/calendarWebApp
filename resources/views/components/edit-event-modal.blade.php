<div id="edit-event-modal" class="fixed inset-y-0 right-0 z-[999] w-120 transform translate-x-full transition-transform duration-300 ease-in-out">
    <div class="h-full bg-gray-50 shadow-xl shadow-black/10">
        <div class="p-8 h-full overflow-y-auto shadow-[-8px_0_15px_-3px_rgba(0,0,0,0.1)]">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Edit Event</h2>
                <button onclick="window.closeEditModal()" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="edit-event-form" method="POST" class="space-y-5">
                @csrf
                <input type="hidden" id="edit-event-id" name="id">
                <input type="hidden" id="edit-event-color" name="color" value="#616161">

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Event Visibility</label>
                    <div class="relative space-y-2">
                        @php
                            $user = auth()->user();
                            $userUnit = $user->organizationalUnit;
                            $isAdmin = $user->division === 'institute';

                            // Get all organizational units
                            $sectors = \App\Models\OrganizationalUnit::where('type', 'sector')->get();
                            $divisions = \App\Models\OrganizationalUnit::where('type', 'division')->get();
                        @endphp

                        <!-- Organizational Units Dropdown -->
                        <div class="relative">
                            <button id="editOrganizationalUnitsButton" data-dropdown-toggle="editOrganizationalUnitsDropdown"
                                class="w-full text-left bg-white border border-gray-300 rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors inline-flex items-center justify-between"
                                type="button">
                                <span id="editSelectedUnitsText">Select organizational units</span>
                                <svg class="w-4 h-4 ml-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                </svg>
                            </button>

                            <!-- Dropdown menu -->
                            <div id="editOrganizationalUnitsDropdown" class="z-10 hidden w-full bg-white rounded-lg shadow-lg border border-gray-200 mt-1">
                                <!-- Role-based Permissions Header -->
                                <div class="p-3 border-b border-gray-200">
                                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Your Permissions:</h3>
                                    @if($isAdmin)
                                        <p class="text-xs text-gray-600">You can create events visible to all organizational units or any combination of sectors and divisions.</p>
                                    @elseif($userUnit && $userUnit->type === 'sector')
                                        <p class="text-xs text-gray-600">You can create events visible to your entire sector or specific divisions within it.</p>
                                    @else
                                        <p class="text-xs text-gray-600">You can only create events visible to your own division.</p>
                                    @endif
                                </div>

                                <ul class="p-3 space-y-1 text-sm text-gray-700" aria-labelledby="editOrganizationalUnitsButton">
                                    <!-- Global Event Option -->
                                    @if($isAdmin || ($userUnit && $userUnit->type === 'sector' && $userUnit->name === 'Admin'))
                                    <li class="border-b border-gray-200 pb-2 mb-2">
                                        <div class="flex items-center p-2 rounded hover:bg-gray-50">
                                            <input type="checkbox" name="is_global" id="edit-is_global" value="1"
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                            <label for="edit-is_global" class="w-full ms-2 text-sm font-medium text-gray-900">
                                                Global Event (Visible to Everyone)
                                            </label>
                                        </div>
                                    </li>
                                    @endif

                                    @if($isAdmin)
                                        <!-- Admin can see all sectors and divisions -->
                                        @foreach($sectors->where('name', '!=', 'Admin') as $sector)
                                            <li class="font-medium text-gray-900 px-2 py-1">{{ $sector->name }}</li>
                                            <li>
                                                <div class="flex items-center p-2 rounded hover:bg-gray-50">
                                                    <input type="checkbox"
                                                        name="organizational_unit_ids[]"
                                                        value="{{ $sector->id }}"
                                                        id="edit-sector-{{ $sector->id }}"
                                                        class="edit-sector-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                        data-sector-id="{{ $sector->id }}">
                                                    <label for="edit-sector-{{ $sector->id }}" class="w-full ms-2 text-sm font-medium text-gray-900">
                                                        {{ $sector->name }} (Entire Sector)
                                                    </label>
                                                </div>
                                            </li>
                                            @foreach($divisions->where('parent_id', $sector->id) as $division)
                                                <li>
                                                    <div class="flex items-center p-2 rounded hover:bg-gray-50 ml-4">
                                                        <input type="checkbox"
                                                            name="organizational_unit_ids[]"
                                                            value="{{ $division->id }}"
                                                            id="edit-division-{{ $division->id }}"
                                                            class="edit-division-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                            data-sector-id="{{ $sector->id }}">
                                                        <label for="edit-division-{{ $division->id }}" class="w-full ms-2 text-sm text-gray-700">
                                                            {{ $division->name }}
                                                        </label>
                                                    </div>
                                                </li>
                                            @endforeach
                                        @endforeach
                                    @elseif($userUnit && $userUnit->type === 'sector' && $userUnit->name === 'Admin')
                                        <!-- Admin sector head can select from Research and Development sectors -->
                                        @foreach($sectors->where('name', '!=', 'Admin') as $sector)
                                            <li class="font-medium text-gray-900 px-2 py-1">{{ $sector->name }}</li>
                                            <li>
                                                <div class="flex items-center p-2 rounded hover:bg-gray-50">
                                                    <input type="checkbox"
                                                        name="organizational_unit_ids[]"
                                                        value="{{ $sector->id }}"
                                                        id="edit-sector-{{ $sector->id }}"
                                                        class="edit-sector-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                        data-sector-id="{{ $sector->id }}">
                                                    <label for="edit-sector-{{ $sector->id }}" class="w-full ms-2 text-sm font-medium text-gray-900">
                                                        {{ $sector->name }} (Entire Sector)
                                                    </label>
                                                </div>
                                            </li>
                                            @foreach($divisions->where('parent_id', $sector->id) as $division)
                                                <li>
                                                    <div class="flex items-center p-2 rounded hover:bg-gray-50 ml-4">
                                                        <input type="checkbox"
                                                            name="organizational_unit_ids[]"
                                                            value="{{ $division->id }}"
                                                            id="edit-division-{{ $division->id }}"
                                                            class="edit-division-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                            data-sector-id="{{ $sector->id }}">
                                                        <label for="edit-division-{{ $division->id }}" class="w-full ms-2 text-sm text-gray-700">
                                                            {{ $division->name }}
                                                        </label>
                                                    </div>
                                                </li>
                                            @endforeach
                                        @endforeach
                                    @elseif($userUnit && $userUnit->type === 'sector')
                                        <!-- Regular sector heads can see only their sector and its divisions -->
                                        <li class="font-medium text-gray-900 px-2 py-1">{{ $userUnit->name }}</li>
                                        <li>
                                            <div class="flex items-center p-2 rounded hover:bg-gray-50">
                                                <input type="checkbox"
                                                    name="organizational_unit_ids[]"
                                                    value="{{ $userUnit->id }}"
                                                    id="edit-sector-{{ $userUnit->id }}"
                                                    class="edit-sector-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                    data-sector-id="{{ $userUnit->id }}">
                                                <label for="edit-sector-{{ $userUnit->id }}" class="w-full ms-2 text-sm font-medium text-gray-900">
                                                    {{ $userUnit->name }} (Entire Sector)
                                                </label>
                                            </div>
                                        </li>
                                        @foreach($divisions->where('parent_id', $userUnit->id) as $division)
                                            <li>
                                                <div class="flex items-center p-2 rounded hover:bg-gray-50 ml-4">
                                                    <input type="checkbox"
                                                        name="organizational_unit_ids[]"
                                                        value="{{ $division->id }}"
                                                        id="edit-division-{{ $division->id }}"
                                                        class="edit-division-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                        data-sector-id="{{ $userUnit->id }}">
                                                    <label for="edit-division-{{ $division->id }}" class="w-full ms-2 text-sm text-gray-700">
                                                        {{ $division->name }}
                                                    </label>
                                                </div>
                                            </li>
                                        @endforeach
                                    @elseif($userUnit)
                                        <!-- Division Head and Employees can only see their division -->
                                        <li>
                                            <div class="flex items-center p-2 rounded hover:bg-gray-50">
                                                <input type="checkbox"
                                                    name="organizational_unit_ids[]"
                                                    value="{{ $userUnit->id }}"
                                                    id="edit-division-{{ $userUnit->id }}"
                                                    class="edit-division-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                    checked
                                                    disabled>
                                                <label for="edit-division-{{ $userUnit->id }}" class="w-full ms-2 text-sm text-gray-700">
                                                    {{ $userUnit->name }}
                                                </label>
                                            </div>
                                        </li>
                                    @else
                                        <li class="text-center py-2 text-gray-500">No organizational units available</li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        <strong>Visibility Rules:</strong><br>
                        • Global: Visible to everyone<br>
                        • Sector: Events for an entire sector with multiple divisions<br>
                        • Division: Events that only affect one division
                    </p>
                </div>

                <div class="space-y-2">
                    <label for="edit-title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" name="title" id="edit-title" required
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <div class="space-y-2">
                    <label for="edit-description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="edit-description" rows="3"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="edit-start-date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="datetime-local" name="start_date" id="edit-start-date" required
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>
                    <div class="space-y-2">
                        <label for="edit-end-date" class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="datetime-local" name="end_date" id="edit-end-date" required
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="edit-guest-input" class="block text-sm font-medium text-gray-700">Guests</label>
                    <div id="edit-guest-container" class="flex flex-wrap gap-2 p-2 bg-white border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 transition-colors">
                        <input id="edit-guest-input" name="guest_input" type="email" class="outline-none border-none flex-grow p-1"
                            placeholder="Type email and press Enter">
                    </div>
                    <input type="hidden" name="guests" id="edit-guest-hidden" value="[]">
                </div>

                <div class="space-y-2">
                    <label for="edit-location" class="block text-sm font-medium text-gray-700">Location</label>
                    <input type="text" name="location" id="edit-location"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_all_day" value="0">
                        <input type="checkbox" name="is_all_day" id="edit-is-all-day" value="1" class="sr-only peer">
                        <div class="relative w-12 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 transition-colors duration-200">
                            <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform duration-200 peer-checked:translate-x-6"></div>
                        </div>
                        <span class="ml-3 text-sm font-medium text-gray-700">All Day Event</span>
                    </label>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="private" value="0">
                        <input type="checkbox" name="private" id="edit-private" value="1" class="sr-only peer">
                        <div class="relative w-12 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 transition-colors duration-200">
                            <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform duration-200 peer-checked:translate-x-6"></div>
                        </div>
                        <span class="ml-3 text-sm font-medium text-gray-700">Private Event</span>
                    </label>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_priority" value="0">
                        <input type="checkbox" name="is_priority" id="edit-is-priority" value="1" class="sr-only peer">
                        <div class="relative w-12 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 transition-colors duration-200">
                            <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform duration-200 peer-checked:translate-x-6"></div>
                        </div>
                        <span class="ml-3 text-sm font-medium text-gray-700">Priority Event</span>
                    </label>
                </div>

                <div class="text-xs text-gray-500 mt-1">
                    <strong>Priority Event:</strong> When enabled, this event will prevent other events from being created in the same time slot for the selected organizational units.
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" id="cancel-edit-btn"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button type="button" id="sync-to-google-btn" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2" />
                        </svg>
                        Sync to Google
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Update Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Define editGuests variable in global scope
let editGuests = [];

// IMPORTANT: Define openEditModal immediately in the global scope
// This ensures it's available as soon as the script is loaded
window.openEditModal = function(event) {
    console.log('openEditModal function called!', event);

    // Safeguard against missing event object
    if (!event) {
        console.error('No event data provided to openEditModal');
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No event data available for editing',
            confirmButtonColor: '#22c55e'
        });
        return;
    }

    // Error check - if we got an error object instead of an event
    if (event.error) {
        console.error('Error data provided to openEditModal:', event.error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: event.error || 'Failed to load event data',
            confirmButtonColor: '#22c55e'
        });
        return;
    }

    // Only block editing for Google events
    if (event.isGoogleEvent) {
        Swal.fire({
            icon: 'warning',
            title: 'Not Editable',
            text: 'Google Calendar events cannot be edited from this app.',
            confirmButtonColor: '#22c55e'
        });
        return;
    }

    // Check for required properties
    if (!event.start || !event.id) {
        console.error('Invalid event data provided to openEditModal:', event);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Invalid event data - missing required properties',
            confirmButtonColor: '#22c55e'
        });
        return;
    }

    // Verify current user has permission to edit this event
    const currentUserId = {{ auth()->id() }};
    const eventCreatorId = parseInt(event.extendedProps.user_id) || null;

    // Log for debugging
    console.log('Edit check - Current user:', currentUserId, 'Creator:', eventCreatorId);
    console.log('Full event data:', event);

    // Allow edit if user is admin or the creator
    const isAdmin = {{ auth()->user()->is_admin ? 'true' : 'false' }};
    const isAdminSectorHead = "{{ auth()->user()->organizationalUnit->name ?? '' }}" === "Admin" && "{{ auth()->user()->organizationalUnit->type ?? '' }}" === "sector";
    const isDivisionHead = {{ auth()->user()->is_division_head ? 'true' : 'false' }};
    const isDivisionEmployee = !isAdmin && !isAdminSectorHead && !isDivisionHead;

    // Set the form action with the event ID
    const form = document.getElementById('edit-event-form');
    form.action = `/OJT/calendarWebApp/events/${event.id}`;

    // Check permissions
    if (!isAdmin && !isAdminSectorHead && !isDivisionHead && eventCreatorId && currentUserId != eventCreatorId) {
        Swal.fire({
            icon: 'error',
            title: 'Permission Denied',
            text: 'Only the creator of this event can edit it',
            confirmButtonColor: '#22c55e'
        });
        return;
    }

    console.log('Opening edit modal with event:', event);

    const modal = document.getElementById('edit-event-modal');
    modal.classList.remove('translate-x-full');
    document.getElementById('calendar-container').classList.add('mr-120');

    // Add backdrop
    const backdrop = document.createElement('div');
    backdrop.id = 'edit-backdrop';
    backdrop.className = 'fixed inset-0 bg-black/3 z-[998] transition-opacity duration-300';
    backdrop.onclick = window.closeEditModal;
    document.body.appendChild(backdrop);

    document.body.style.overflow = 'hidden';

    // Store whether this is a Google event - ensure we capture this explicitly
    window.currentEditingGoogleEvent = event.isGoogleEvent || false;
    console.log('Is Google event:', window.currentEditingGoogleEvent);

    // Handle both API and FullCalendar event formats
    const eventData = {
        id: event.id || '',
        title: event.title || 'Untitled Event',
        start: event.start,
        end: event.end,
        allDay: event.allDay || false,
        backgroundColor: event.backgroundColor || '#3b82f6',
        extendedProps: event.extendedProps || {},
        // Add extra properties for consistency
        organizational_unit_ids: event.organizational_unit_ids || [],
        is_global: event.is_global || false,
        is_priority: event.is_priority || false
    };

    // Set form fields
    document.getElementById('edit-event-id').value = eventData.id;
    document.getElementById('edit-title').value = eventData.title;

    // Handle description - might be directly on event or in extendedProps
    const description = eventData.extendedProps.description || event.description || '';
    document.getElementById('edit-description').value = description;

    // Handle location - might be directly on event or in extendedProps
    const location = eventData.extendedProps.location || event.location || '';
    document.getElementById('edit-location').value = location;

    // All-day checkbox
    document.getElementById('edit-is-all-day').checked = eventData.allDay;

    // Private checkbox
    const isPrivate = eventData.extendedProps.private || event.private || false;
    document.getElementById('edit-private').checked = isPrivate;

    // Priority checkbox
    const isPriority = eventData.extendedProps.is_priority || event.is_priority || false;
    document.getElementById('edit-is-priority').checked = isPriority;

    // Set calendar type
    const calendarType = eventData.extendedProps.calendar_type ||
                         eventData.extendedProps.calendarType ||
                         event.calendar_type ||
                         'division';

    // Handle both admin and non-admin cases for calendar type
    if (document.getElementById('edit-calendar_type')) {
        document.getElementById('edit-calendar_type').value = calendarType;
    }

    // Fix date handling with extra validation
    try {
        const startDate = new Date(eventData.start);
        const endDate = eventData.end ? new Date(eventData.end) : new Date(startDate);

        // Validate dates before proceeding
        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
            throw new Error('Invalid date value');
        }

        // Format dates for input fields - use correct timezone handling
        // Convert to local timezone by adjusting for the offset
        const startLocal = new Date(startDate.getTime() - (startDate.getTimezoneOffset() * 60000));
        const endLocal = new Date(endDate.getTime() - (endDate.getTimezoneOffset() * 60000));

        document.getElementById('edit-start-date').value = startLocal.toISOString().slice(0, 16);
        document.getElementById('edit-end-date').value = endLocal.toISOString().slice(0, 16);
    } catch (err) {
        console.error('Error processing event dates:', err);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Invalid event date format',
            confirmButtonColor: '#22c55e'
        });
        window.closeEditModal();
        return;
    }

    // Set guests
    editGuests = eventData.extendedProps.guests || event.guests || [];
    document.getElementById('edit-guest-hidden').value = JSON.stringify(editGuests);
    const guestContainer = document.getElementById('edit-guest-container');
    const guestInput = document.getElementById('edit-guest-input');

    Array.from(guestContainer.children).forEach(child => {
        if (child !== guestInput) child.remove();
    });

    editGuests.forEach(email => createEditGuestTag(email));

    // Handle organizational units visibility
    // Get organizational unit IDs from the event data
    let organizationalUnitIds = [];

    // Try to get IDs from all possible sources in the event data structure
    if (event.extendedProps && Array.isArray(event.extendedProps.organizational_unit_ids)) {
        organizationalUnitIds = event.extendedProps.organizational_unit_ids;
    } else if (Array.isArray(event.organizational_unit_ids)) {
        organizationalUnitIds = event.organizational_unit_ids;
    }

    // Convert all IDs to numbers for consistent comparison
    const numericOrgUnitIds = organizationalUnitIds.map(id => parseInt(id));

    // Determine if event is global based on available data
    const isGlobal = (event.extendedProps && event.extendedProps.is_global) ||
                     event.is_global ||
                     numericOrgUnitIds.length === 0;

    console.log('Event Data:', event);
    console.log('Organizational Unit IDs:', numericOrgUnitIds);
    console.log('Is Global:', isGlobal);

    // Set the is_global checkbox
    const globalCheckbox = document.getElementById('edit-is_global');
    if (globalCheckbox) {
        globalCheckbox.checked = isGlobal;
    }

    // Toggle visibility of organizational units dropdown based on global setting
    const orgUnitsContainer = document.getElementById('editOrganizationalUnitsContainer');
    if (orgUnitsContainer) {
        orgUnitsContainer.style.display = isGlobal ? 'none' : 'block';
    }

    // For division employees and division heads, automatically set their division
    if (isDivisionEmployee || isDivisionHead) {
        const divisionCheckbox = document.querySelector('input[name="organizational_unit_ids[]"]');
        if (divisionCheckbox) {
            divisionCheckbox.checked = true;
            divisionCheckbox.disabled = true;
        }
    } else {
        // For admin users or sector heads, pre-select organizational units
        if (!isGlobal) {
            // Pre-select checkboxes based on the event's organizational unit IDs
            document.querySelectorAll('input[name="organizational_unit_ids[]"]').forEach(checkbox => {
                const checkboxId = parseInt(checkbox.value);
                // Check if this unit ID is in the event's organizational units
                checkbox.checked = numericOrgUnitIds.includes(checkboxId);

                // If this is a sector checkbox, handle divisions accordingly
                if (checkbox.classList.contains('edit-sector-checkbox')) {
                    const sectorId = checkbox.dataset.sectorId;
                    const divisionCheckboxes = document.querySelectorAll(`.edit-division-checkbox[data-sector-id="${sectorId}"]`);

                    // If sector is checked, all its divisions should be disabled and considered checked
                    if (checkbox.checked) {
                        divisionCheckboxes.forEach(divCheckbox => {
                            divCheckbox.disabled = true;
                        });
                    } else {
                        // If sector not checked, enable divisions and check each if it's in the unit IDs
                        divisionCheckboxes.forEach(divCheckbox => {
                            const divId = parseInt(divCheckbox.value);
                            divCheckbox.disabled = false;
                            divCheckbox.checked = numericOrgUnitIds.includes(divId);
                        });
                    }
                }
            });

            // After setting all checkboxes, update the displayed text
            updateEditSelectedUnitsText();
        }
    }

    // Update the selected units text
    updateEditSelectedUnitsText();

    // Show/hide Sync to Google button based on event status and Google authentication
    const syncButton = document.getElementById('sync-to-google-btn');
    const calendarEl = document.getElementById('calendar');
    const isAuthenticated = calendarEl && calendarEl.getAttribute('data-is-authenticated') === 'true';

    // Only show the sync button if:
    // 1. It's a local event (not from Google)
    // 2. User is authenticated with Google
    // 3. Event doesn't have a Google event ID already
    const isLocalEvent = !event.isGoogleEvent && !window.currentEditingGoogleEvent;
    const hasGoogleId = event.google_event_id || eventData.extendedProps.google_event_id;

    if (isLocalEvent && isAuthenticated && !hasGoogleId) {
        syncButton.classList.remove('hidden');
    } else {
        syncButton.classList.add('hidden');
    }

    // If Google event but not authenticated, show warning
    if (window.currentEditingGoogleEvent) {
        const calendarEl = document.getElementById('calendar');
        const isAuthenticated = calendarEl && calendarEl.getAttribute('data-is-authenticated') === 'true';
        if (!isAuthenticated) {
            window.closeEditModal();
            Swal.fire({
                title: 'Google Authentication Required',
                text: 'You need to connect your Google account to edit Google Calendar events',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#22c55e',
                confirmButtonText: 'Connect Google Account',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ route('google.auth') }}";
                }
            });
            return;
        }
    }
};

// Also make closeEditModal globally available
window.closeEditModal = function() {
    const modal = document.getElementById('edit-event-modal');
    modal.classList.add('translate-x-full');
    document.getElementById('calendar-container').classList.remove('mr-120');

    // Remove backdrop
    const backdrop = document.getElementById('edit-backdrop');
    if (backdrop) {
        backdrop.remove();
    }

    document.body.style.overflow = '';
};

function createEditGuestTag(email) {
    const guestContainer = document.getElementById('edit-guest-container');
    const guestInput = document.getElementById('edit-guest-input');

    const span = document.createElement("span");
    span.className = "px-2 py-1 bg-gray-100 rounded-full text-sm flex items-center group";
    span.textContent = email;

    const removeBtn = document.createElement("button");
    removeBtn.innerHTML = "&times;";
    removeBtn.className = "ml-2 text-gray-400 hover:text-red-500 transition-colors";
    removeBtn.onclick = function() {
        editGuests = editGuests.filter(g => g !== email);
        span.remove();
        updateEditHiddenInput();
    };

    span.appendChild(removeBtn);
    guestContainer.insertBefore(span, guestInput);
}

document.addEventListener('DOMContentLoaded', function() {
    // Add click handler for cancel button
    document.getElementById('cancel-edit-btn').addEventListener('click', function() {
        window.closeEditModal();
    });

    const form = document.getElementById('edit-event-form');
    const guestInput = document.getElementById('edit-guest-input');

    // Guest input handling
    guestInput.addEventListener('keydown', async function(event) {
        if (event.key !== "Enter") return;
        event.preventDefault();

        const email = this.value.trim();
        if (!email) return;

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            this.classList.add('border-red-500');
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Email',
                text: 'Please enter a valid email address',
                confirmButtonColor: '#22c55e'
            });
            return;
        }

        if (!editGuests.includes(email)) {
            editGuests.push(email);
            createEditGuestTag(email);
            updateEditHiddenInput();  // Add this function call
        }
        this.value = "";
        this.classList.remove('border-red-500');
    });

    // Replace the click outside handler
    document.addEventListener('mousedown', function(event) {
        if (document.querySelector('.swal2-container')) {
            return;
        }
        const modal = document.getElementById('edit-event-modal');
        if (!modal) return;

        const modalContent = modal.querySelector('.h-full');
        if (!modalContent) return;

        if (!modal.classList.contains('translate-x-full') && !modalContent.contains(event.target)) {
            window.closeEditModal();
        }
    }, true);

    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Check if there's a pending email in the guest input field
        const guestInput = document.getElementById('edit-guest-input');
        const pendingEmail = guestInput.value.trim();

        if (pendingEmail) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(pendingEmail)) {
                // Add the pending email to the guest list
                if (!editGuests.includes(pendingEmail)) {
                    editGuests.push(pendingEmail);
                    createEditGuestTag(pendingEmail);
                    updateEditHiddenInput();  // Use this function instead of direct assignment
                }
                guestInput.value = "";
            } else {
                // Invalid email format - show error and prevent submission
                guestInput.classList.add('border-red-500');
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Email',
                    text: 'Please enter a valid email address or clear the guest input field.',
                    confirmButtonColor: '#22c55e'
                });
                return;
            }
        }

        const formData = new FormData(form);
        const eventId = document.getElementById('edit-event-id').value;

        try {
            // Add loading state to the button
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Updating...
            `;

            const response = await fetch(`/OJT/calendarWebApp/events/${eventId}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'  // Explicitly request JSON response
                }
            });

            // Restore button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;

            // Handle response based on content type
            const contentType = response.headers.get('content-type');
            let data;

            if (contentType && contentType.includes('application/json')) {
                // If it's JSON, parse it normally
                data = await response.json();
            } else {
                // If not JSON, handle as plain text and create a user-friendly error
                const textResponse = await response.text();
                console.error('Server returned non-JSON response:', textResponse);

                // Create a structured error object to maintain consistency with JSON responses
                data = {
                    success: false,
                    error: 'Server returned an unexpected response format. Please try again or contact support.'
                };

                // Log the first 500 characters of the response for debugging
                if (textResponse.length > 0) {
                    console.error('Response preview:', textResponse.substring(0, 500) + '...');
                }

                // If we got here but the response was actually OK, consider it a success
                // This handles cases where the server returns HTML but the operation succeeded
                if (response.ok) {
                    closeEditModal();
                    calendar.refetchEvents();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Event updated successfully',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    return;
                }
            }

            if (response.ok) {
                // Success - close modal and refresh calendar
                closeEditModal();
                calendar.refetchEvents();
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Event updated successfully',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else if (response.status === 422 && data.overlapping_events) {
                // Show warning with overlapping events
                const overlappingEvents = data.overlapping_events;
                let html = '<div class="text-left">';
                html += '<p class="mb-3">This event overlaps with the following priority events:</p>';
                html += '<ul class="list-disc pl-5 space-y-2">';
                overlappingEvents.forEach(event => {
                    const start = new Date(event.start_date).toLocaleString();
                    const end = new Date(event.end_date).toLocaleString();
                    html += `<li class="text-sm">
                        <strong>${event.title}</strong><br>
                        <span class="text-gray-600">${start} - ${end}</span><br>
                        <span class="text-gray-500">${event.organizational_units.join(', ')}</span>
                    </li>`;
                });
                html += '</ul></div>';

                const result = await Swal.fire({
                    title: 'Warning: Overlapping Events',
                    html: html,
                    icon: 'warning',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonText: 'Proceed anyway',
                    denyButtonText: 'Edit',
                    cancelButtonText: 'Cancel',
                    width: '600px'
                });

                if (result.isConfirmed) {
                    // User chose to proceed - submit with force_update flag
                    formData.append('force_update', '1');
                    const forceResponse = await fetch(`/OJT/calendarWebApp/events/${eventId}`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (forceResponse.ok) {
                        closeEditModal();
                        calendar.refetchEvents();
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Event updated successfully',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        throw new Error('Failed to update event');
                    }
                } else if (result.isDenied) {
                    // User chose to edit - keep modal open
                    return;
                } else {
                    // User chose to cancel - close modal
                    closeEditModal();
                }
            } else {
                // Handle general errors
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.error || data.message || 'Failed to update event',
                    confirmButtonColor: '#22c55e'
                });
            }
        } catch (error) {
            console.error('Error:', error);

            // Check if the event was actually created despite the error
            // This is a common scenario when the server returns non-JSON but the operation succeeds
            setTimeout(() => {
                calendar.refetchEvents();
            }, 1000);

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An unexpected error occurred, but your event might have been saved. The calendar will refresh shortly.',
                confirmButtonText: 'OK'
            });
        }
    });

    // Handle global checkbox
    const globalCheckbox = document.getElementById('edit-is_global');
    if (globalCheckbox) {
        globalCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="organizational_unit_ids[]"]');

            checkboxes.forEach(checkbox => {
                checkbox.disabled = this.checked;
                if (this.checked) {
                    checkbox.checked = false;
                }
            });

            updateEditSelectedUnitsText();
        });
    }

    // Initialize dropdown functionality
    initializeEditDropdown();
    initializeEditCheckboxes();
});

// Add this missing function to update the hidden input with guest emails
function updateEditHiddenInput() {
    document.getElementById('edit-guest-hidden').value = JSON.stringify(editGuests);
}

// Initialize edit dropdown toggle
function initializeEditDropdown() {
    const dropdownButton = document.getElementById('editOrganizationalUnitsButton');
    const dropdown = document.getElementById('editOrganizationalUnitsDropdown');

    if (!dropdownButton || !dropdown) {
        console.error('Edit dropdown elements not found');
        return;
    }

    dropdownButton.addEventListener('click', function(e) {
        e.preventDefault();
        dropdown.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (dropdownButton && dropdown && !dropdown.classList.contains('hidden') &&
            !dropdownButton.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.add('hidden');
        }
    });
}

// Initialize edit checkboxes
function initializeEditCheckboxes() {
    // Global checkbox
    const globalCheckbox = document.getElementById('edit-is_global');
    if (globalCheckbox) {
        globalCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="organizational_unit_ids[]"]');

            checkboxes.forEach(checkbox => {
                checkbox.disabled = this.checked;
                if (this.checked) {
                    checkbox.checked = false;
                }
            });

            updateEditSelectedUnitsText();
        });
    }

    // Sector checkboxes
    document.querySelectorAll('.edit-sector-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const sectorId = this.dataset.sectorId;
            const divisionCheckboxes = document.querySelectorAll(`.edit-division-checkbox[data-sector-id="${sectorId}"]`);

            divisionCheckboxes.forEach(divCheckbox => {
                divCheckbox.checked = this.checked;
                divCheckbox.disabled = this.checked;
            });

            updateEditSelectedUnitsText();
        });
    });

    // Division checkboxes
    document.querySelectorAll('.edit-division-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const sectorId = this.dataset.sectorId;
            const sectorCheckbox = document.querySelector(`.edit-sector-checkbox[data-sector-id="${sectorId}"]`);
            const divisionCheckboxes = document.querySelectorAll(`.edit-division-checkbox[data-sector-id="${sectorId}"]`);

            if (sectorCheckbox) {
                // If all divisions are checked, check the sector
                const allChecked = Array.from(divisionCheckboxes).every(cb => cb.checked);
                sectorCheckbox.checked = allChecked;
            }

            updateEditSelectedUnitsText();
        });
    });
}

function updateEditSelectedUnitsText() {
    const selectedCheckboxes = document.querySelectorAll('input[name="organizational_unit_ids[]"]:checked:not(:disabled)');
    const selectedText = document.getElementById('editSelectedUnitsText');
    const isGlobalCheckbox = document.getElementById('edit-is_global');

    if (isGlobalCheckbox && isGlobalCheckbox.checked) {
        selectedText.textContent = 'Global Event';
    } else if (selectedCheckboxes.length === 0) {
        selectedText.textContent = 'Select organizational units';
    } else if (selectedCheckboxes.length === 1) {
        selectedText.textContent = selectedCheckboxes[0].nextElementSibling.textContent.trim();
    } else {
        selectedText.textContent = `${selectedCheckboxes.length} units selected`;
    }
}
</script>

