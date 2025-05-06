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
        // Store user information for JavaScript access
        window.userInfo = {
            division: "{{ auth()->user()->division }}",
            isDivisionHead: {{ auth()->user()->is_division_head ? 'true' : 'false' }},
            isAdmin: {{ auth()->user()->division === 'institute' ? 'true' : 'false' }}
        };
    </script>
</head>

<body data-user-division="{{ auth()->user()->division }}" data-is-division-head="{{ auth()->user()->is_division_head ? 'true' : 'false' }}">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <x-sidebar />

        <!-- Main content -->
        <div class="flex flex-col flex-1 ml-80">
            <!-- Page content -->
            <main class="flex-1">
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
