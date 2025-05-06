<aside class="fixed top-0 left-0 h-full w-80 bg-green-800 border-r flex flex-col">
    <div class="flex items-center justify-center h-16 border-b border-green-700">
        <a href="/" class="text-xl font-semibold text-white">Calendar</a>
    </div>

    <div class="flex-grow p-4 space-y-4">
        <!-- Welcome message -->
        <div class="mb-6">
            <p class="text-white text-sm">Welcome,</p>
            <p class="text-white font-semibold">{{ Auth::user()->name }}</p>
            <p class="text-gray-300 text-xs">Division: {{ ucfirst(str_replace('_', ' - ', Auth::user()->division)) }}</p>
            @if(Auth::user()->is_division_head)
                <p class="text-green-300 text-xs mt-1">Role: Division Head</p>
            @elseif(Auth::user()->division === 'institute')
                <p class="text-green-300 text-xs mt-1">Role: Administrator</p>
            @else
                <p class="text-gray-300 text-xs mt-1">Role: Division Member</p>
            @endif
        </div>

        <button onclick="openModal()" class="block w-full py-2 px-4 text-left text-white bg-green-900 rounded-lg hover:bg-green-700">
            Create Event
        </button>

        <a href="{{ route('home') }}" class="block py-2 px-4 text-white bg-green-900 rounded-lg hover:text-white">
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
            <div class="text-xs text-gray-500 mt-1">
                Google Calendar connection is needed to view, create, and edit Google Calendar events.
            </div>

            <!-- Add sync all to Google Calendar section -->
            <div class="border-t mt-4 pt-4">
                <h3 class="text-sm font-semibold text-gray-700">Sync Operations</h3>
                <button id="sync-all-to-google" class="mt-2 px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-xs w-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                    Sync All Events to Google
                </button>
                <div class="text-xs text-gray-500 mt-1">
                    Push all your local calendar events to Google Calendar.
                </div>
            </div>
        </div>

        <!-- Calendar Filters -->
        <div class="mt-4">
            <h3 class="text-white font-medium mb-2">Calendar Filters</h3>
            <div class="space-y-2">
                <label class="flex items-center text-white cursor-pointer">
                    <input type="checkbox" class="calendar-filter form-checkbox text-green-500 rounded" value="institute">
                    <span class="ml-2">Institute-wide Events</span>
                </label>

                @if(auth()->user()->division === 'institute')
                    <!-- Admin-only filters, grouped by sector with divisions as sub-items -->
                    <div class="ml-2">
                        <span class="text-green-200 text-xs">Sectors</span>
                        <div class="mb-1">
                            <label class="flex items-center text-white cursor-pointer ml-2">
                                <input type="checkbox" class="calendar-filter form-checkbox text-green-500 rounded" value="sector1">
                                <span class="ml-2 font-semibold">Sector 1 Events</span>
                            </label>
                        </div>
                        <div class="mb-1">
                            <label class="flex items-center text-white cursor-pointer ml-2">
                                <input type="checkbox" class="calendar-filter form-checkbox text-green-500 rounded" value="sector2">
                                <span class="ml-2 font-semibold">Sector 2 Events</span>
                            </label>
                        </div>
                        <div class="mb-1">
                            <label class="flex items-center text-white cursor-pointer ml-2">
                                <input type="checkbox" class="calendar-filter form-checkbox text-green-500 rounded" value="sector3">
                                <span class="ml-2 font-semibold">Sector 3 Events</span>
                            </label>
                        </div>
                        <div class="mb-1">
                            <label class="flex items-center text-white cursor-pointer ml-2">
                                <input type="checkbox" class="calendar-filter form-checkbox text-green-500 rounded" value="sector4">
                                <span class="ml-2 font-semibold">Sector 4 Events</span>
                            </label>
                        </div>
                    </div>
                @elseif(auth()->user()->is_division_head)
                    @php
                        $userDivision = auth()->user()->division;
                        $userSector = explode('_', $userDivision)[0];
                    @endphp
                    <label class="flex items-center text-white cursor-pointer">
                        <input type="checkbox" class="calendar-filter form-checkbox text-green-500 rounded" value="{{ $userSector }}">
                        <span class="ml-2">{{ ucfirst($userSector) }} (All Divisions)</span>
                    </label>
                    <label class="flex items-center text-white cursor-pointer ml-4">
                        <input type="checkbox" class="calendar-filter form-checkbox text-green-500 rounded" value="{{ $userDivision }}">
                        <span class="ml-2">{{ ucfirst(str_replace('_', ' - ', $userDivision)) }} Only</span>
                    </label>
                @else
                    @php
                        $userDivision = auth()->user()->division;
                    @endphp
                    <label class="flex items-center text-white cursor-pointer">
                        <input type="checkbox" class="calendar-filter form-checkbox text-green-500 rounded" value="{{ $userDivision }}">
                        <span class="ml-2">Division Events</span>
                    </label>
                @endif

                <label class="flex items-center text-white cursor-pointer">
                    <input type="checkbox" class="calendar-filter form-checkbox text-green-500 rounded" value="google">
                    <span class="ml-2">Google Calendar</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Logout button at the bottom -->
    <div class="p-4 border-t border-green-700 mt-auto">
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" class="w-full py-2 px-4 bg-red-600 text-white text-sm rounded hover:bg-red-700 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout
            </button>
        </form>
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ...existing initialization code...

        // Toggle handlers for sectoral and sector collapsible sections
        if (document.querySelector('.sectoral-toggle')) {
            document.querySelector('.sectoral-toggle').addEventListener('click', function(e) {
                e.stopPropagation();
                document.querySelector('.sectoral-subsections').classList.toggle('hidden');
                this.classList.toggle('rotate-180');
            });
        }

        document.querySelectorAll('.sector-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                const divisions = this.closest('div').querySelector('.sector-divisions');
                divisions.classList.toggle('hidden');
                this.classList.toggle('rotate-180');
            });
        });

        // Initialize filters with new logic for division employees
        const calendarFilters = document.querySelectorAll('.calendar-filter');
        const userDivision = "{{ auth()->user()->division }}";
        const isDivisionHead = {{ auth()->user()->is_division_head ? 'true' : 'false' }};
        const isAdmin = userDivision === 'institute';

        try {
            // Set appropriate default filters based on user role
            let defaultFilters;
            if (isAdmin) {
                defaultFilters = [
                    'institute',
                    'sector1', 'sector2', 'sector3', 'sector4',
                    'sector1_div1', 'sector2_div1', 'sector3_div1', 'sector4_div1',
                    'google'
                ];
            } else if (isDivisionHead) {
                const userSector = userDivision.split('_')[0];
                defaultFilters = ['institute', userSector, userDivision, 'google'];
            } else {
                defaultFilters = ['institute', userDivision, 'google'];
            }

            // Load saved filters or use defaults
            const savedFilters = localStorage.getItem('calendarFilters')
                ? JSON.parse(localStorage.getItem('calendarFilters'))
                : defaultFilters;

            // Apply filters based on user role
            calendarFilters.forEach(checkbox => {
                const value = checkbox.value;
                // Only disable for non-admins
                if (isAdmin) {
                    checkbox.disabled = false;
                } else if (isDivisionHead) {
                    const userSector = userDivision.split('_')[0];
                    checkbox.disabled = !['institute', userSector, userDivision, 'google'].includes(value);
                } else {
                    checkbox.disabled = !['institute', userDivision, 'google'].includes(value);
                }

                checkbox.checked = savedFilters.includes(value);
            });

            // Update filter handler
            const updateFiltersAndCalendar = () => {
                let checkedFilters = Array.from(calendarFilters)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                // Enforce role-based filter restrictions
                if (isAdmin) {
                    // Don't restrict what filters admins can use - they should see everything they select
                    // Remove this line: checkedFilters = checkedFilters.filter(f => ['institute', 'sectoral', 'division_head', 'google'].includes(f));
                } else if (isDivisionHead) {
                    const userSector = userDivision.split('_')[0];
                    checkedFilters = checkedFilters.filter(f => ['institute', userSector, userDivision, 'google'].includes(f));
                } else {
                    checkedFilters = checkedFilters.filter(f => ['institute', userDivision, 'google'].includes(f));
                }

                localStorage.setItem('calendarFilters', JSON.stringify(checkedFilters));
                if (window.calendar) {
                    window.calendar.refetchEvents();
                }
            };

            // Add event listeners
            calendarFilters.forEach(checkbox => {
                checkbox.addEventListener('change', updateFiltersAndCalendar);
            });

            // Initialize Google Calendar status
            updateGoogleAuthStatus();
        } catch (error) {
            console.error('Error initializing sidebar:', error);
        }
    });

    // Google Calendar disconnect handler
    function disconnectGoogle() {
        fetch('{{ route("google.disconnect") }}')
            .then(response => response.json())
            .then(data => {
                if (data.forceRefresh) {
                    sessionStorage.removeItem('connected_google_account');
                    window.clearAllGoogleEvents?.();
                    updateGoogleAuthStatus();
                } else {
                    window.location.reload(true);
                }
            })
            .catch(error => {
                console.error('Google disconnect error:', error);
                updateGoogleAuthStatus();
            });
    }

    // Google Calendar status update handler
    function updateGoogleAuthStatus() {
        fetch('{{ route("google.status") }}')
            .then(response => response.json())
            .then(data => {
                const elements = {
                    status: document.getElementById('sidebar-google-status'),
                    connect: document.getElementById('sidebar-connect-btn'),
                    disconnect: document.getElementById('sidebar-disconnect-btn'),
                    account: document.getElementById('sidebar-google-account'),
                    syncAll: document.getElementById('sync-all-to-google')
                };

                if (data.authenticated) {
                    elements.status.textContent = 'Connected ✓';
                    elements.status.classList.add('text-green-300');
                    elements.connect.classList.add('hidden');
                    elements.disconnect.classList.remove('hidden');

                    // Show sync all button only when authenticated
                    if (elements.syncAll) {
                        elements.syncAll.classList.remove('hidden');
                    }

                    if (data.email) {
                        const previousAccount = sessionStorage.getItem('connected_google_account');
                        sessionStorage.setItem('connected_google_account', data.email);
                        elements.account.textContent = `Account: ${data.email}`;
                        elements.account.classList.remove('hidden');

                        if (previousAccount && previousAccount !== data.email) {
                            window.refreshGoogleEvents?.();
                        }
                    }
                } else {
                    elements.status.textContent = 'Not Connected';
                    elements.status.classList.remove('text-green-300');
                    elements.connect.classList.remove('hidden');
                    elements.disconnect.classList.add('hidden');
                    elements.account.classList.add('hidden');
                    sessionStorage.removeItem('connected_google_account');

                    // Hide sync all button when not authenticated
                    if (elements.syncAll) {
                        elements.syncAll.classList.add('hidden');
                    }
                }
            })
            .catch(error => console.error('Google auth status error:', error));
    }

    // Handle sync all events to Google
    document.addEventListener('DOMContentLoaded', function() {
        const syncAllButton = document.getElementById('sync-all-to-google');
        if (syncAllButton) {
            syncAllButton.addEventListener('click', function() {
                // Only proceed if user is authenticated with Google
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
                            window.location.href = "{{ route('google.auth') }}";
                        }
                    });
                    return;
                }

                // Show confirmation dialog
                Swal.fire({
                    title: 'Sync All Events to Google',
                    text: 'This will push all your local calendar events to Google Calendar. Already synced events will be skipped. Continue?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#22c55e',
                    confirmButtonText: 'Yes, Sync All',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state
                        syncAllButton.disabled = true;
                        const originalText = syncAllButton.innerHTML;
                        syncAllButton.innerHTML = `
                            <svg class="animate-spin h-4 w-4 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Syncing...
                        `;

                        // Make API call to sync all events
                        fetch('{{ route("events.sync-all-to-google") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            // Restore button state
                            syncAllButton.disabled = false;
                            syncAllButton.innerHTML = originalText;

                            if (data.success) {
                                // Show success message with details
                                let message = `Successfully synced ${data.results.success.length} events to Google Calendar.`;
                                if (data.results.skipped.length > 0) {
                                    message += `\n${data.results.skipped.length} events were already synced.`;
                                }
                                if (data.results.failed.length > 0) {
                                    message += `\n${data.results.failed.length} events failed to sync.`;
                                }

                                Swal.fire({
                                    title: 'Sync Complete',
                                    text: message,
                                    icon: 'success',
                                    confirmButtonColor: '#22c55e'
                                }).then(() => {
                                    // Refresh calendar to show updated events
                                    if (window.calendar && typeof window.calendar.refetchEvents === 'function') {
                                        window.calendar.refetchEvents();
                                    }
                                });
                            } else {
                                // Show error message
                                Swal.fire({
                                    title: 'Sync Failed',
                                    text: data.message || 'Failed to sync events to Google Calendar',
                                    icon: 'error',
                                    confirmButtonColor: '#22c55e'
                                });
                            }
                        })
                        .catch(error => {
                            // Restore button state and show error
                            syncAllButton.disabled = false;
                            syncAllButton.innerHTML = originalText;

                            console.error('Error syncing events to Google:', error);
                            Swal.fire({
                                title: 'Sync Error',
                                text: 'An error occurred while syncing events. Please try again.',
                                icon: 'error',
                                confirmButtonColor: '#22c55e'
                            });
                        });
                    }
                });
            });
        }
    });
</script>
