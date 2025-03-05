<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;

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
        $event = Event::find($id);

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
        $event = Event::create([
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,

            'location' => $request->location,
            'user_id' => auth()->id(),
            'is_all_day' => $request->is_all_day ?? false,
            'status' => $request->status ?? 'pending',
            'color' => $request->color, // Save selected color

        ]);

        return redirect()->route('home')->with('success', 'Event created successfully!');
    }




    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
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
