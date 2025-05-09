<aside class="fixed top-0 left-0 h-full w-80 flex flex-col shadow-xl" style="background-color: #121e38;">
    <!-- Header -->
    <div class="flex items-center justify-center h-16 border-b border-gray-700/30">
        <a href="/" class="text-xl font-semibold text-white hover:text-gray-300 transition-colors">Calendar</a>
    </div>

    <div class="flex-grow p-5 space-y-6">
        <!-- User Profile Section -->
        <div class="mb-6">
            <div class="flex items-center space-x-3 mb-3">
                <div class="w-10 h-10 rounded-full bg-gray-700/50 flex items-center justify-center ring-2 ring-gray-600/30">
                    <span class="text-gray-200 font-medium text-sm">{{ substr(Auth::user()->name, 0, 1) }}</span>
                </div>
                <div>
                    <p class="text-white font-medium">{{ Auth::user()->name }}</p>
                    <p class="text-gray-400 text-xs">{{ ucfirst(str_replace('_', ' - ', Auth::user()->division)) }}</p>
                </div>
            </div>
            @if(Auth::user()->is_division_head)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/20 text-blue-300 border border-blue-500/30">
                    Division Head
                </span>
            @elseif(Auth::user()->division === 'institute')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-500/20 text-purple-300 border border-purple-500/30">
                    Administrator
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-500/20 text-gray-300 border border-gray-500/30">
                    Division Member
                </span>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="space-y-2">
            <button onclick="openModal()" class="w-full py-2.5 px-4 text-left text-gray-200 bg-gray-700/30 rounded-lg hover:bg-gray-700/50 transition-colors flex items-center group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400 group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Event
            </button>

            <a href="{{ route('home') }}" class="block py-2.5 px-4 text-gray-200 bg-gray-700/30 rounded-lg hover:bg-gray-700/50 transition-colors flex items-center group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400 group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Home
            </a>
        </div>

        <!-- Google Calendar Section -->
        <div class="bg-gray-700/20 rounded-lg p-4 border border-gray-700/30">
            <h3 class="text-sm font-medium text-gray-200 mb-3 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Google Calendar
            </h3>
            <div id="sidebar-google-status" class="text-sm text-gray-400 mb-2">Checking status...</div>
            <div id="sidebar-google-account" class="text-xs text-gray-500 mb-3 italic hidden"></div>
            
            <div class="flex flex-col space-y-2">
                <button id="sidebar-connect-btn" onclick="window.location.href='{{ route('google.auth') }}'"
                    class="py-2 px-3 bg-blue-500/20 text-blue-300 text-sm rounded-lg hover:bg-blue-500/30 transition-colors flex items-center justify-center border border-blue-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                    Connect Google Calendar
                </button>
                <button id="sidebar-disconnect-btn" onclick="disconnectGoogle()"
                    class="py-2 px-3 bg-red-500/20 text-red-300 text-sm rounded-lg hover:bg-red-500/30 transition-colors flex items-center justify-center hidden border border-red-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Disconnect
                </button>
            </div>

            <!-- Sync Section -->
            <div class="mt-4 pt-4 border-t border-gray-700/30">
                <button id="sync-all-to-google" class="w-full py-2 px-3 bg-gray-700/30 text-gray-300 rounded-lg hover:bg-gray-700/50 transition-colors text-sm flex items-center justify-center border border-gray-700/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Sync All Events
                </button>
            </div>
        </div>

        <!-- Calendar Filters -->
        <div>
            <h3 class="text-sm font-medium text-gray-200 mb-3 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Calendar Filters
            </h3>
            <div class="space-y-2">
                <label class="flex items-center text-gray-200 cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox" class="calendar-filter sr-only peer" value="institute">
                        <div class="w-5 h-5 bg-gray-700/30 border border-gray-600/50 rounded-md peer-checked:bg-blue-500/20 peer-checked:border-blue-500/50 transition-colors group-hover:border-gray-500/70"></div>
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity">
                            <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                    <span class="ml-2 text-sm group-hover:text-gray-300">Institute-wide Events</span>
                </label>

                @if(auth()->user()->division === 'institute')
                    <div class="ml-2 space-y-2">
                        <span class="text-xs font-medium text-gray-400 block mt-3 mb-1">Sectors</span>
                        <div class="space-y-1">
                            <label class="flex items-center text-gray-200 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" class="calendar-filter sr-only peer" value="sector1">
                                    <div class="w-5 h-5 bg-gray-700/30 border border-gray-600/50 rounded-md peer-checked:bg-blue-500/20 peer-checked:border-blue-500/50 transition-colors group-hover:border-gray-500/70"></div>
                                    <div class="absolute inset-0 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity">
                                        <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                </div>
                                <span class="ml-2 text-sm group-hover:text-gray-300">Sector 1 Events</span>
                            </label>
                            <label class="flex items-center text-gray-200 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" class="calendar-filter sr-only peer" value="sector2">
                                    <div class="w-5 h-5 bg-gray-700/30 border border-gray-600/50 rounded-md peer-checked:bg-blue-500/20 peer-checked:border-blue-500/50 transition-colors group-hover:border-gray-500/70"></div>
                                    <div class="absolute inset-0 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity">
                                        <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                </div>
                                <span class="ml-2 text-sm group-hover:text-gray-300">Sector 2 Events</span>
                            </label>
                            <label class="flex items-center text-gray-200 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" class="calendar-filter sr-only peer" value="sector3">
                                    <div class="w-5 h-5 bg-gray-700/30 border border-gray-600/50 rounded-md peer-checked:bg-blue-500/20 peer-checked:border-blue-500/50 transition-colors group-hover:border-gray-500/70"></div>
                                    <div class="absolute inset-0 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity">
                                        <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                </div>
                                <span class="ml-2 text-sm group-hover:text-gray-300">Sector 3 Events</span>
                            </label>
                            <label class="flex items-center text-gray-200 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" class="calendar-filter sr-only peer" value="sector4">
                                    <div class="w-5 h-5 bg-gray-700/30 border border-gray-600/50 rounded-md peer-checked:bg-blue-500/20 peer-checked:border-blue-500/50 transition-colors group-hover:border-gray-500/70"></div>
                                    <div class="absolute inset-0 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity">
                                        <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                </div>
                                <span class="ml-2 text-sm group-hover:text-gray-300">Sector 4 Events</span>
                            </label>
                        </div>
                    </div>
                @elseif(auth()->user()->is_division_head)
                    @php
                        $userDivision = auth()->user()->division;
                        $userSector = explode('_', $userDivision)[0];
                    @endphp
                    <label class="flex items-center text-gray-200 cursor-pointer group">
                        <div class="relative">
                            <input type="checkbox" class="calendar-filter sr-only peer" value="{{ $userSector }}">
                            <div class="w-5 h-5 bg-gray-700/30 border border-gray-600/50 rounded-md peer-checked:bg-blue-500/20 peer-checked:border-blue-500/50 transition-colors group-hover:border-gray-500/70"></div>
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity">
                                <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <span class="ml-2 text-sm group-hover:text-gray-300">{{ ucfirst($userSector) }} (All Divisions)</span>
                    </label>
                    <label class="flex items-center text-gray-200 cursor-pointer group ml-4">
                        <div class="relative">
                            <input type="checkbox" class="calendar-filter sr-only peer" value="{{ $userDivision }}">
                            <div class="w-5 h-5 bg-gray-700/30 border border-gray-600/50 rounded-md peer-checked:bg-blue-500/20 peer-checked:border-blue-500/50 transition-colors group-hover:border-gray-500/70"></div>
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity">
                                <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <span class="ml-2 text-sm group-hover:text-gray-300">{{ ucfirst(str_replace('_', ' - ', $userDivision)) }} Only</span>
                    </label>
                @else
                    @php
                        $userDivision = auth()->user()->division;
                    @endphp
                    <label class="flex items-center text-gray-200 cursor-pointer group">
                        <div class="relative">
                            <input type="checkbox" class="calendar-filter sr-only peer" value="{{ $userDivision }}">
                            <div class="w-5 h-5 bg-gray-700/30 border border-gray-600/50 rounded-md peer-checked:bg-blue-500/20 peer-checked:border-blue-500/50 transition-colors group-hover:border-gray-500/70"></div>
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity">
                                <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <span class="ml-2 text-sm group-hover:text-gray-300">Division Events</span>
                    </label>
                @endif

                <label class="flex items-center text-gray-200 cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox" class="calendar-filter sr-only peer" value="google">
                        <div class="w-5 h-5 bg-gray-700/30 border border-gray-600/50 rounded-md peer-checked:bg-blue-500/20 peer-checked:border-blue-500/50 transition-colors group-hover:border-gray-500/70"></div>
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity">
                            <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                    <span class="ml-2 text-sm group-hover:text-gray-300">Google Calendar</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Logout Button -->
    <div class="p-5 border-t border-gray-700/30 mt-auto">
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" class="w-full py-2.5 px-4 bg-gray-700/30 text-gray-200 text-sm rounded-lg hover:bg-gray-700/50 transition-colors flex items-center justify-center group border border-gray-700/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-400 group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
