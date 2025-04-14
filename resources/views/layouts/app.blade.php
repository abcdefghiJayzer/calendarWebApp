<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Calendar' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Initialize Swal globally
        window.Swal = Swal;
    </script>
</head>

<body>
    <div class="flex h-screen">
        <!-- Sidebar -->
        <x-sidebar />

        <!-- Main content -->
        <div class="flex flex-col flex-1 ml-64">
            <!-- Top navigation bar -->
            <header class="bg-white shadow-sm">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <h1 class="text-xl font-semibold text-gray-900">Calendar</h1>

                    <!-- User menu -->
                    @auth
                        <div class="flex items-center">
                            <span class="text-gray-700 mr-4">{{ Auth::user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-gray-600 hover:text-gray-900">
                                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 p-4">
                @if(session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Event creation modal -->
            <x-add-event-modal />

            <!-- Event edit modal -->
            <x-edit-event-modal />

            <!-- Event details modal -->
            <x-event-details-modal />
        </div>
    </div>

    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/main.min.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <!-- App JS -->
    <script>
        // ...existing scripts...
    </script>

    <script src="{{ asset('js/app.js') }}"></script>
</body>

</html>
