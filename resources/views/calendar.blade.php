@extends('layouts.app')

@section('content')
<div class="">
    <div id="calendar" data-events-url="{{ route('getEvents') }}" class="bg-white rounded-lg shadow p-4 h-[96vh]"></div>
</div>






@endsection
