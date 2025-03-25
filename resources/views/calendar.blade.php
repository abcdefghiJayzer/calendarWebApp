@extends('layouts.app')

@vite(['resources/js/app.js'])

@section('content')
<div class="">
    <div id="calendar"
        data-events-url="{{ route('getEvents') }}"
        data-api-key="{{ config('services.google.calendar.api_key') }}"
        data-calendar-id="{{ config('services.google.calendar.calendar_id') }}"
        class="bg-white rounded-lg shadow p-4 h-[96vh]"></div>
</div>
@endsection
