<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Calendar' }}</title>

    @vite('resources/css/app.css')
    @vite('resources/js/app.js')


    @vite('resources/js/calendar.js')


    <!-- <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script> -->

</head>


<body class="flex">
    <x-sidebar />

    <main class="flex-1 p-4 ml-64">
        @yield('content')
    </main>
</body>



</html>
