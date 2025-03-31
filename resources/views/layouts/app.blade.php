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
    <!-- Main content -->
    <div class="flex">
        <x-sidebar />
        <main class="flex-1 p-4 ml-64">
            @yield('content')
        </main>
    </div>

    <!-- Modal layer -->
    <x-add-event-modal />
    <x-event-details-modal />
    <x-edit-event-modal />

</body>

</html>
