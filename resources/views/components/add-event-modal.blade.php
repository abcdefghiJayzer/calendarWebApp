<div id="add-event-modal" class="fixed inset-y-0 right-0 z-[999] w-120 transform translate-x-full transition-transform duration-300 ease-in-out">
    <div class="h-full bg-gray-50 shadow-xl shadow-black/10">
        <div class="p-8 h-full overflow-y-auto shadow-[-8px_0_15px_-3px_rgba(0,0,0,0.1)]">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Create Event</h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="add-event-form" action="{{ route('events.store') }}" method="POST" class="space-y-5">
                @csrf
                @php
                    // Define color variables
                    $adminColor = '#33b679';
                    $sectoralHeadColor = '#039be5';
                    $divisionHeadColor = '#e8b4bc';
                    $divisionEmployeeColor = '#616161';

                    // Get user and determine color based on role
                    $user = auth()->user();

                    // Set default color based on user role
                    $defaultColor = $divisionEmployeeColor; // default color

                    switch($user->role) {
                        case 'admin':
                            $defaultColor = $adminColor;
                            break;
                        case 'sector_head':
                            $defaultColor = $sectoralHeadColor;
                            break;
                        case 'division_head':
                            $defaultColor = $divisionHeadColor;
                            break;
                        default:
                            $defaultColor = $divisionEmployeeColor;
                    }
                @endphp

                <!-- Hidden color input -->
                <input type="hidden" name="color" id="event-color" value="{{ $defaultColor }}">

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
                            <button id="organizationalUnitsButton" data-dropdown-toggle="organizationalUnitsDropdown"
                                class="w-full text-left bg-white border border-gray-300 rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors inline-flex items-center justify-between"
                                type="button">
                                <span id="selectedUnitsText">Select organizational units</span>
                                <svg class="w-4 h-4 ml-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                </svg>
                            </button>

                            <!-- Dropdown menu -->
                            <div id="organizationalUnitsDropdown" class="z-10 hidden w-full bg-white rounded-lg shadow-lg border border-gray-200 mt-1">
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

                                <ul class="p-3 space-y-1 text-sm text-gray-700" aria-labelledby="organizationalUnitsButton">
                                    <!-- Global Event Option -->
                                    @if($isAdmin || ($userUnit && $userUnit->type === 'sector' && $userUnit->name === 'Admin'))
                                    <li class="border-b border-gray-200 pb-2 mb-2">
                                        <div class="flex items-center p-2 rounded hover:bg-gray-50">
                                            <input type="checkbox" name="is_global" id="is_global" value="1"
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                            <label for="is_global" class="w-full ms-2 text-sm font-medium text-gray-900">
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
                                                        id="sector-{{ $sector->id }}"
                                                        class="sector-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                        data-sector-id="{{ $sector->id }}">
                                                    <label for="sector-{{ $sector->id }}" class="w-full ms-2 text-sm font-medium text-gray-900">
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
                                                            id="division-{{ $division->id }}"
                                                            class="division-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                            data-sector-id="{{ $sector->id }}">
                                                        <label for="division-{{ $division->id }}" class="w-full ms-2 text-sm text-gray-700">
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
                                                        id="sector-{{ $sector->id }}"
                                                        class="sector-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                        data-sector-id="{{ $sector->id }}">
                                                    <label for="sector-{{ $sector->id }}" class="w-full ms-2 text-sm font-medium text-gray-900">
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
                                                            id="division-{{ $division->id }}"
                                                            class="division-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                            data-sector-id="{{ $sector->id }}">
                                                        <label for="division-{{ $division->id }}" class="w-full ms-2 text-sm text-gray-700">
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
                                                    id="sector-{{ $userUnit->id }}"
                                                    class="sector-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                    data-sector-id="{{ $userUnit->id }}">
                                                <label for="sector-{{ $userUnit->id }}" class="w-full ms-2 text-sm font-medium text-gray-900">
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
                                                        id="division-{{ $division->id }}"
                                                        class="division-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                        data-sector-id="{{ $userUnit->id }}">
                                                    <label for="division-{{ $division->id }}" class="w-full ms-2 text-sm text-gray-700">
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
                                                    id="division-{{ $userUnit->id }}"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                    checked
                                                    disabled>
                                                <label for="division-{{ $userUnit->id }}" class="w-full ms-2 text-sm font-medium text-gray-900">
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
                    <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" name="title" id="title" required
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <div class="space-y-2">
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="3"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="datetime-local" name="start_date" id="start_date" required
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div class="space-y-2">
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="datetime-local" name="end_date" id="end_date"
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Add guests</label>
                    <div id="guest-container" class="flex flex-wrap gap-2 p-2 bg-white border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 transition-colors">
                        <input id="guest-input" type="text" class="outline-none border-none flex-grow p-1" placeholder="Type email and press space or comma">
                    </div>
                    <input type="hidden" name="guests" id="guest-hidden" value="[]">
                </div>

                <div class="space-y-2">
                    <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                    <input type="text" name="location" id="location"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_all_day" value="0">
                        <input type="checkbox" name="is_all_day" id="is-all-day" value="1" class="sr-only peer">
                        <div class="relative w-12 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 transition-colors duration-200">
                            <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform duration-200 peer-checked:translate-x-6"></div>
                        </div>
                        <span class="ml-3 text-sm font-medium text-gray-700">All Day Event</span>
                    </label>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="private" value="0">
                        <input type="checkbox" name="private" id="private" value="1" class="sr-only peer">
                        <div class="relative w-12 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 transition-colors duration-200">
                            <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform duration-200 peer-checked:translate-x-6"></div>
                        </div>
                        <span class="ml-3 text-sm font-medium text-gray-700">Private Event</span>
                    </label>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_priority" value="0">
                        <input type="checkbox" name="is_priority" id="is-priority" value="1" class="sr-only peer">
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
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Create Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Add color variables to JavaScript
    const COLORS = {
        admin: '#33b679',
        sectoralHead: '#039be5',
        divisionHead: '#e8b4bc',
        divisionEmployee: '#616161'
    };

    let guests = [];

    function createGuestTag(email) {
        const tag = document.createElement('div');
        tag.className = 'flex items-center gap-1 bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm';
        tag.innerHTML = `
            <span>${email}</span>
            <button type="button" class="text-blue-600 hover:text-blue-800" onclick="removeGuest('${email}')">×</button>
        `;
        return tag;
    }

    function openModal(startDate = null, endDate = null) {
        const modal = document.getElementById('add-event-modal');
        modal.classList.remove('translate-x-full');
        document.getElementById('calendar-container').classList.add('mr-120');

        // Set the event color based on user role
        const userRole = "{{ auth()->user()->role }}";

        let color = COLORS.divisionEmployee; // default color

        switch(userRole) {
            case 'admin':
                color = COLORS.admin;
                break;
            case 'sector_head':
                color = COLORS.sectoralHead;
                break;
            case 'division_head':
                color = COLORS.divisionHead;
                break;
            default:
                color = COLORS.divisionEmployee;
        }

        document.getElementById('event-color').value = color;

        if (startDate) {
            // Format dates for input fields - use correct timezone handling
            const startLocal = new Date(startDate);
            const endLocal = endDate ? new Date(endDate) : new Date(startDate);

            // Ensure we're working with local dates
            startLocal.setMinutes(startLocal.getMinutes() - startLocal.getTimezoneOffset());
            endLocal.setMinutes(endLocal.getMinutes() - endLocal.getTimezoneOffset());

            document.getElementById('start_date').value = startLocal.toISOString().slice(0, 16);
            document.getElementById('end_date').value = endLocal.toISOString().slice(0, 16);
        }

        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.id = 'add-backdrop';
        backdrop.className = 'fixed inset-0 bg-black/2 z-[998] transition-opacity duration-300';
        backdrop.onclick = closeModal;
        document.body.appendChild(backdrop);

        // Prevent body scroll
        document.body.style.overflow = 'hidden';

        // Focus on the title input field
        setTimeout(() => {
            document.getElementById('title').focus();
        }, 100);
    }

    function closeModal() {
        const modal = document.getElementById('add-event-modal');
        modal.classList.add('translate-x-full');
        document.getElementById('calendar-container').classList.remove('mr-120');

        // Remove backdrop
        const backdrop = document.getElementById('add-backdrop');
        if (backdrop) {
            backdrop.remove();
        }

        // Restore body scroll
        document.body.style.overflow = '';
        document.getElementById('add-event-form').reset();
        guests = [];
        document.getElementById('guest-hidden').value = '[]';
        const guestContainer = document.getElementById('guest-container');
        const guestInput = document.getElementById('guest-input');
        Array.from(guestContainer.children).forEach(child => {
            if (child !== guestInput) child.remove();
        });
    }

    function updateSelectedUnitsText() {
        const selectedCheckboxes = document.querySelectorAll('input[name="organizational_unit_ids[]"]:checked:not(:disabled)');
        const selectedText = document.getElementById('selectedUnitsText');
        const isGlobalCheckbox = document.getElementById('is_global');

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

    // Initialize dropdown toggle
    function initializeDropdown() {
        const dropdownButton = document.getElementById('organizationalUnitsButton');
        const dropdown = document.getElementById('organizationalUnitsDropdown');

        if (!dropdownButton || !dropdown) {
            console.error('Dropdown elements not found');
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

    // Initialize sector and division checkboxes
    function initializeCheckboxes() {
        // Global checkbox
        const globalCheckbox = document.getElementById('is_global');
        if (globalCheckbox) {
            globalCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('input[name="organizational_unit_ids[]"]');

                checkboxes.forEach(checkbox => {
                    checkbox.disabled = this.checked;
                    if (this.checked) {
                        checkbox.checked = false;
                    }
                });

                updateSelectedUnitsText();
            });
        }

        // Sector checkboxes
        document.querySelectorAll('.sector-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const sectorId = this.dataset.sectorId;
                const divisionCheckboxes = document.querySelectorAll(`.division-checkbox[data-sector-id="${sectorId}"]`);

                divisionCheckboxes.forEach(divCheckbox => {
                    divCheckbox.checked = this.checked;
                    divCheckbox.disabled = this.checked;
                });

                updateSelectedUnitsText();
            });
        });

        // Division checkboxes
        document.querySelectorAll('.division-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const sectorId = this.dataset.sectorId;
                const sectorCheckbox = document.querySelector(`.sector-checkbox[data-sector-id="${sectorId}"]`);
                const divisionCheckboxes = document.querySelectorAll(`.division-checkbox[data-sector-id="${sectorId}"]`);

                if (sectorCheckbox) {
                    // If all divisions are checked, check the sector
                    const allChecked = Array.from(divisionCheckboxes).every(cb => cb.checked);
                    sectorCheckbox.checked = allChecked;
                }

                updateSelectedUnitsText();
            });
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        // Automatically select organizational unit for employees and division heads
        const userUnit = document.querySelector('input[name="organizational_unit_ids[]"]');
        if (userUnit && userUnit.checked) {
            updateSelectedUnitsText();
        }

        // Initialize dropdowns and checkboxes
        initializeDropdown();
        initializeCheckboxes();

        // Initialize guest input handling
        const guestInput = document.getElementById('guest-input');
        const guestContainer = document.getElementById('guest-container');
        const guestHidden = document.getElementById('guest-hidden');

        function updateHiddenInput() {
            guestHidden.value = JSON.stringify(guests);
        }

        window.removeGuest = function(email) {
            guests = guests.filter(g => g !== email);
            updateHiddenInput();
            const tags = guestContainer.querySelectorAll('.bg-blue-100');
            tags.forEach(tag => {
                if (tag.querySelector('span').textContent === email) {
                    tag.remove();
                }
            });
        };

        guestInput.addEventListener('input', function(e) {
            const value = e.target.value.trim();
            if (value.endsWith(' ') || value.endsWith(',')) {
                const email = value.slice(0, -1).trim();
                if (email && !guests.includes(email)) {
                    if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        guests.push(email);
                        guestContainer.insertBefore(createGuestTag(email), guestInput);
                        updateHiddenInput();
                    }
                }
                e.target.value = '';
            }
        });

        guestInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // Prevent form submission
                const email = this.value.trim();
                if (email && !guests.includes(email)) {
                    if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        guests.push(email);
                        guestContainer.insertBefore(createGuestTag(email), guestInput);
                        updateHiddenInput();
                        this.value = '';
                    } else if (email) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Email',
                            text: 'Please enter a valid email address',
                            confirmButtonColor: '#22c55e'
                        });
                    }
                }
            }
        });

        guestInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const emails = pastedText.split(/[\s,]+/).filter(email => email.trim());

            emails.forEach(email => {
                if (email && !guests.includes(email)) {
                    if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        guests.push(email);
                        guestContainer.insertBefore(createGuestTag(email), guestInput);
                    }
                }
            });
            updateHiddenInput();
        });
    });

    // Form submission handler
    document.getElementById('add-event-form').addEventListener('submit', async function(e) {
        // Check if the event was triggered by pressing Enter in the guest input
        if (document.activeElement === document.getElementById('guest-input')) {
            e.preventDefault();
            return;
        }

        e.preventDefault();

        try {
            // Handle any pending guest input
            const emailInput = document.getElementById('guest-input');
            const email = emailInput.value.trim();
            if (email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailRegex.test(email)) {
                    if (!guests.includes(email)) {
                        guests.push(email);
                        createGuestTag(email);
                        // Define the update function inline to avoid the reference error
                        document.getElementById('guest-hidden').value = JSON.stringify(guests);
                    }
                    emailInput.value = "";
                } else {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Invalid Email',
                        text: 'Please enter a valid email address or clear the guest input field',
                        confirmButtonColor: '#22c55e'
                    });
                    return;
                }
            }

            // Check if at least one organizational unit is selected or global is checked
            const isGlobalCheckbox = document.getElementById('is_global');
            const isGlobal = isGlobalCheckbox ? isGlobalCheckbox.checked : false;
            const selectedUnits = document.querySelectorAll('input[name="organizational_unit_ids[]"]:checked');

            if (!isGlobal && selectedUnits.length === 0) {
                await Swal.fire({
                    icon: 'error',
                    title: 'Visibility Required',
                    text: 'Please select at least one organizational unit or mark as global event',
                    confirmButtonColor: '#22c55e'
                });
                return;
            }

            const formData = new FormData(this);

            // Add loading state to the button
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Creating...
            `;

            const response = await fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // Restore button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;

            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response');
            }

            const data = await response.json();
            console.log('Response:', data); // Debug log

            if (response.ok) {
                // Success
                closeModal();
                calendar.refetchEvents();
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Event created successfully',
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
                    // User chose to proceed - submit with force_create flag
                    formData.append('force_create', '1');
                    const forceResponse = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (forceResponse.ok) {
                        closeModal();
                        calendar.refetchEvents();
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Event created successfully',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        throw new Error('Failed to create event');
                    }
                } else if (result.isDenied) {
                    // User chose to edit - keep modal open
                    return;
                } else {
                    // User chose to cancel - close modal
                    closeModal();
                }
            } else {
                throw new Error(data.error || 'Failed to create event');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to create event',
                confirmButtonText: 'OK'
            });
        }
    });

    // Click outside handler
    document.addEventListener('mousedown', function(event) {
        if (document.querySelector('.swal2-container')) {
            return;
        }

        const modal = document.getElementById('add-event-modal');
        if (!modal) return;

        const modalContent = modal.querySelector('.h-full');
        if (modal && !modal.classList.contains('translate-x-full') && modalContent && !modalContent.contains(event.target)) {
            closeModal();
        }
    }, true);

    // Add closeAddModal as an alias to closeModal
    window.closeAddModal = closeModal;

    // Initialize Tom Select for guests
    const guestsSelect = new TomSelect('#guests', {
        plugins: ['remove_button'],
        persist: false,
        create: true,
        createOnBlur: true,
        createFilter: function(input) {
            return input.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/);
        },
        onItemAdd: function(value, item) {
            // Validate email format
            if (!value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                this.removeItem(value);
                return false;
            }
        }
    });

    // If editing, populate the form with event data
    if (event) {
        document.getElementById('event-title').value = event.title;
        document.getElementById('event-description').value = event.extendedProps?.description || '';
        document.getElementById('event-location').value = event.extendedProps?.location || '';
        document.getElementById('event-start').value = event.start;
        document.getElementById('event-end').value = event.end || event.start;
        document.getElementById('event-all-day').checked = event.allDay;
        document.getElementById('event-private').checked = event.extendedProps?.private || false;
        document.getElementById('event-priority').checked = event.extendedProps?.is_priority || false;

        // Set guests
        if (event.extendedProps?.guests && Array.isArray(event.extendedProps.guests)) {
            guestsSelect.clear();
            event.extendedProps.guests.forEach(guest => {
                guestsSelect.addItem(guest);
            });
        }

        // Set organizational units
        if (event.extendedProps?.organizational_unit_ids) {
            const orgUnitSelect = document.getElementById('organizational-unit-ids');
            if (orgUnitSelect) {
                event.extendedProps.organizational_unit_ids.forEach(id => {
                    const option = orgUnitSelect.querySelector(`option[value="${id}"]`);
                    if (option) {
                        option.selected = true;
                    }
                });
            }
        }
    }
</script>
