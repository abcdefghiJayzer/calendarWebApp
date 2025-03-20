<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventGuest;

class CalendarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        // Return the calendar view and pass events
        return view('calendar');
    }


    public function getEvents()
    {
        $events = Event::select('id', 'title', 'start_date as start', 'end_date as end', 'location', 'color as backgroundColor')
            ->get();

        return response()->json($events);
    }





    /**
     * Display the specified resource.
     */
    // public function show(string $id)
    // {
    //     $events = Event::all();
    //     return response()->json($events);
    // }

    public function show($id)
    {
        $event = Event::with('participants')->find($id);

        if (!$event) {
            abort(404, 'Event not found');
        }

        return view('event-details', compact('event'));
    }




    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('add');
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|min:1', // Ensure title is not empty or just whitespace
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string',
            'color' => 'required|string',
            'guests' => 'nullable|string',
            'is_all_day' => 'nullable|boolean',
        ]);

        $event = Event::create([
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'location' => $request->location,
            'user_id' => auth()->id(),
            'is_all_day' => $request->is_all_day ?? false,
            'status' => $request->status ?? 'pending',
            'color' => $request->color,
        ]);

        $guestEmails = json_decode($request->guests, true) ?? [];

        foreach ($guestEmails as $email) {
            $guest = EventGuest::firstOrCreate(['email' => $email]);
            $event->participants()->attach($guest->id);
        }

        return redirect()->route('home')->with('success', 'Event created successfully!');
    }




    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $event = Event::findOrFail($id);
        return view('edit', compact('event'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $event = Event::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'guests' => 'nullable|json',
            'location' => 'nullable|string|max:255',
            'color' => 'required|string|max:20',
            'is_all_day' => 'boolean',
        ]);

        // Update event details
        $event->update([
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'location' => $request->location,
            'color' => $request->color,
            'is_all_day' => $request->is_all_day,
        ]);

        // Handle guests update
        $guestEmails = json_decode($request->guests, true) ?? [];

        if (!empty($guestEmails)) {
            $guestIds = [];

            foreach ($guestEmails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $guest = EventGuest::firstOrCreate(['email' => $email]);
                    $guestIds[] = $guest->id;
                }
            }

            $event->participants()->sync($guestIds);
        } else {
            $event->participants()->detach();
        }

        return redirect()->route('home')->with('success', 'Event updated successfully!');
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();
        return response()->json(['message' => 'Event deleted successfully']);
    }
}
