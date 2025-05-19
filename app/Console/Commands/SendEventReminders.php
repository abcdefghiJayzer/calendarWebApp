<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use App\Models\EventGuest;
use App\Mail\EventReminder;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails to event guests 1 day before the event';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        
        // Get all events happening tomorrow
        $events = Event::whereDate('start_date', $tomorrow)->get();
        
        $this->info("Found " . $events->count() . " events scheduled for tomorrow.");
        
        $emailCount = 0;
        
        foreach ($events as $event) {
            // Load all guests for this event
            $guests = $event->participants;
            
            if ($guests->count() === 0) {
                $this->info("Event #{$event->id} ({$event->title}) has no guests.");
                continue;
            }
            
            $this->info("Processing {$guests->count()} guests for event #{$event->id} ({$event->title})");
            
            foreach ($guests as $guest) {
                try {
                    // Send reminder email
                    Mail::to($guest->email)->send(new EventReminder($event, $guest));
                    $emailCount++;
                    
                    $this->info("Sent reminder email to {$guest->email} for event {$event->title}");
                    
                    // Add a small delay to prevent overwhelming the mail server
                    sleep(1);
                } catch (\Exception $e) {
                    Log::error("Failed to send reminder email", [
                        'event_id' => $event->id,
                        'guest_email' => $guest->email,
                        'error' => $e->getMessage()
                    ]);
                    
                    $this->error("Failed to send email to {$guest->email}: {$e->getMessage()}");
                }
            }
        }
        
        $this->info("Completed sending reminders. Sent {$emailCount} reminder emails.");
    }
}
