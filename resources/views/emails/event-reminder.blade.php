@component('mail::message')
# Event Reminder: {{ $event->title }}

Hello,

This is a friendly reminder that you have an event **tomorrow**:

**Event:** {{ $event->title }}  
**Date & Time:** {{ $startDate }}  
**Location:** {{ $location }}

@if($event->description)
**Details:**  
{{ $event->description }}
@endif

@component('mail::button', ['url' => $eventUrl])
View Event Details
@endcomponent

Thank you,<br>
{{ config('app.name') }}
@endcomponent 