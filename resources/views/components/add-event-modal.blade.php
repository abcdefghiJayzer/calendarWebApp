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
                    $divisionEmployeeColor = '#616161';
                    $adminColor = '#2563eb'; // blue-600
                    $sectorHeadColor = '#059669'; // emerald-600
                    $divisionHeadColor = '#7c3aed'; // violet-600
                    
                    // Determine user type and set appropriate color
                    $user = auth()->user();
                    $userUnit = $user->organizationalUnit;
                    $isAdmin = $user->is_admin;
                    $isSectorHead = $userUnit && $userUnit->type === 'sector';
                    $isDivisionHead = $userUnit && $userUnit->type === 'division' && $user->is_division_head;
                    $isDivisionEmployee = $userUnit && $userUnit->type === 'division' && !$user->is_division_head;
                    
                    // Set default color based on user type
                    $defaultColor = $isAdmin ? $adminColor : 
                                  ($isSectorHead ? $sectorHeadColor : 
                                  ($isDivisionHead ? $divisionHeadColor : 
                                  ($isDivisionEmployee ? $divisionEmployeeColor : $adminColor)));
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
                        <input id="guest-input" type="email" class="outline-none border-none flex-grow p-1" placeholder="Type email and press Enter">
                    </div>
                    <input type="hidden" name="guests" id="guest-hidden" value="[]">
                </div>

                <div class="space-y-2">
                    <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                    <input type="text" name="location" id="location"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <div class="flex items-center space-x-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_all_day" value="0">
                        <input type="checkbox" name="is_all_day" id="is_all_day" value="1" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700">All Day Event</span>
                    </label>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="private" value="0">
                        <input type="checkbox" name="private" id="private" value="1" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700">Private Event</span>
                    </label>
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
        divisionEmployee: '#616161',
        admin: '#2563eb',
        sectorHead: '#059669',
        divisionHead: '#7c3aed'
    };

    let guests = [];

    function openModal(startDate = null, endDate = null) {
        const modal = document.getElementById('add-event-modal');
        modal.classList.remove('translate-x-full');
        document.getElementById('calendar-container').classList.add('mr-120');

        // Set the color based on user type
        const userUnit = "{{ auth()->user()->organizationalUnit->type ?? '' }}";
        const isDivisionEmployee = userUnit === 'division' && !{{ auth()->user()->is_division_head ? 'true' : 'false' }};
        const isDivisionHead = userUnit === 'division' && {{ auth()->user()->is_division_head ? 'true' : 'false' }};
        const isSectorHead = userUnit === 'sector';
        const isAdmin = {{ auth()->user()->is_admin ? 'true' : 'false' }};

        let color = COLORS.admin; // default color
        if (isDivisionEmployee) {
            color = COLORS.divisionEmployee;
        } else if (isDivisionHead) {
            color = COLORS.divisionHead;
        } else if (isSectorHead) {
            color = COLORS.sectorHead;
        } else if (isAdmin) {
            color = COLORS.admin;
        }

        document.getElementById('event-color').value = color;

        if (startDate) {
            const startLocal = new Date(startDate.getTime() - startDate.getTimezoneOffset() * 60000);
            document.getElementById('start_date').value = startLocal.toISOString().slice(0, 16);

            if (endDate) {
                const endLocal = new Date(endDate.getTime() - endDate.getTimezoneOffset() * 60000);
                document.getElementById('end_date').value = endLocal.toISOString().slice(0, 16);
            } else {
                document.getElementById('end_date').value = startLocal.toISOString().slice(0, 16);
            }
        }

        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.id = 'add-backdrop';
        backdrop.className = 'fixed inset-0 bg-black/2 z-[998] transition-opacity duration-300';
        backdrop.onclick = closeModal;
        document.body.appendChild(backdrop);

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
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

    function updateHiddenInput() {
        document.getElementById('guest-hidden').value = JSON.stringify(guests);
    }

    function createGuestTag(email) {
        const guestContainer = document.getElementById('guest-container');
        const guestInput = document.getElementById('guest-input');

        const span = document.createElement("span");
        span.className = "px-2 py-1 bg-gray-100 rounded-full text-sm flex items-center group";
        span.textContent = email;

        const removeBtn = document.createElement("button");
        removeBtn.innerHTML = "&times;";
        removeBtn.className = "ml-2 text-gray-400 hover:text-red-500 transition-colors";
        removeBtn.onclick = function() {
            guests = guests.filter(g => g !== email);
            span.remove();
            updateHiddenInput();
        };

        span.appendChild(removeBtn);
        guestContainer.insertBefore(span, guestInput);
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
    });

    document.getElementById('add-event-form').addEventListener('submit', async function(e) {
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
                        updateHiddenInput();
                    }
                    emailInput.value = "";
                } else {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Invalid Email',
                        text: 'Please enter a valid email address',
                        confirmButtonColor: '#22c55e'
                    });
                    return;
                }
            }

            // Check if at least one organizational unit is selected or global is checked
            const isGlobal = document.getElementById('is_global') ? document.getElementById('is_global').checked : false;
            const selectedUnits = document.querySelectorAll('input[name="organizational_unit_ids[]"]:checked');
            
            if (!isGlobal && selectedUnits.length === 0) {
                await Swal.fire({
                    icon: 'error',
                    title: 'Selection Required',
                    text: 'Please select at least one organizational unit or mark the event as global',
                    confirmButtonColor: '#22c55e'
                });
                return;
            }

            const formData = new FormData(this);
            formData.set('is_global', isGlobal ? '1' : '0');  // Explicitly set is_global as string '1' or '0'

            // Add selected organizational units
            if (!isGlobal && selectedUnits.length > 0) {
                selectedUnits.forEach(unit => {
                    formData.append('organizational_unit_ids[]', unit.value);
                });
            }

            // Check for guest scheduling conflicts
            if (guests.length > 0) {
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value || startDate;

                const checkData = new URLSearchParams();
                checkData.append('guests', JSON.stringify(guests));
                checkData.append('start_date', startDate);
                checkData.append('end_date', endDate);
                checkData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                const checkResponse = await fetch('/OJT/calendarWebApp/check-conflicts', {
                    method: 'POST',
                    body: checkData,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const conflictResult = await checkResponse.json();

                if (conflictResult.conflicts && conflictResult.conflicts.length > 0) {
                    let conflictHtml = 'The following guests have scheduling conflicts:<br><br>';

                    conflictResult.conflicts.forEach(conflict => {
                        conflictHtml += `<strong>${conflict.email}</strong> has conflicts with:<br>`;

                        conflict.events.forEach(event => {
                            const startDate = new Date(event.start).toLocaleString();
                            const endDate = new Date(event.end).toLocaleString();
                            conflictHtml += `- <b>${event.title}</b><br>`;
                            conflictHtml += `&nbsp;&nbsp;From: ${startDate}<br>`;
                            conflictHtml += `&nbsp;&nbsp;To: ${endDate}<br>`;
                        });

                        conflictHtml += '<br>';
                    });

                    const result = await Swal.fire({
                        title: 'Schedule Conflict Detected',
                        html: conflictHtml,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#22c55e',
                        cancelButtonColor: '#ef4444',
                        confirmButtonText: 'Add anyway',
                        cancelButtonText: 'Cancel'
                    });

                    if (!result.isConfirmed) {
                        return; // Just return without closing the modal
                    }
                }
            }

            // Proceed with event creation
            const response = await fetch(`${window.baseUrl}/events`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (response.ok) {
                closeModal();
                if (window.calendar) {
                    window.calendar.refetchEvents();
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: result.message || 'Event created successfully!',
                    confirmButtonColor: '#22c55e'
                });
            } else {
                throw new Error(result.message || result.error || 'Failed to create event');
            }

        } catch (error) {
            console.error('Error:', error);

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'There was a problem creating the event',
                confirmButtonColor: '#22c55e'
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
</script>
