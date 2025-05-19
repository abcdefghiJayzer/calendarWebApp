<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Event;
use App\Models\EventGuest;

class EventReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $event;
    public $guest;

    /**
     * Create a new message instance.
     */
    public function __construct(Event $event, EventGuest $guest)
    {
        $this->event = $event;
        $this->guest = $guest;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: ' . $this->event->title . ' (Tomorrow)',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.event-reminder',
            with: [
                'event' => $this->event,
                'guestEmail' => $this->guest->email,
                'eventUrl' => url('/events/' . $this->event->id),
                'startDate' => date('F j, Y, g:i a', strtotime($this->event->start_date)),
                'location' => $this->event->location ?? 'No location specified',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
