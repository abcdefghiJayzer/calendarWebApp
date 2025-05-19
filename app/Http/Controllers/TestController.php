<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\Event;
use App\Models\EventGuest;
use App\Mail\EventReminder;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    public function testEmail()
    {
        try {
            // Create test objects (or you can use existing ones from the database)
            $testEvent = Event::first() ?? new Event([
                'title' => 'Test Event',
                'description' => 'This is a test event',
                'start_date' => now()->addDay(),
                'end_date' => now()->addDay()->addHours(2),
                'location' => 'Test Location'
            ]);

            $testGuest = new EventGuest();
            $testGuest->email = auth()->user()->email ?? 'test@example.com';

            // Send the test email
            Mail::to($testGuest->email)->send(new EventReminder($testEvent, $testGuest));

            // Log the attempt
            Log::info('Test email sent', [
                'to' => $testGuest->email,
                'event' => $testEvent->title
            ]);

            return back()->with('success', 'Test email sent successfully! Check your email or mail trap.');

        } catch (\Exception $e) {
            Log::error('Test email failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }
}
