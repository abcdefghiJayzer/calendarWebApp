<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DivisionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If calendar_type is provided in the request
        if ($request->has('calendar_type')) {
            $calendarType = $request->calendar_type;
            $user = auth()->user();

            if (!$user->canCreateEventsIn($calendarType)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'You do not have permission to create events in this division.'
                    ], 403);
                }

                return redirect()->back()->with('error', 'You do not have permission to create events in this division.');
            }
        }

        return $next($request);
    }
}
