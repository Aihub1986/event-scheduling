<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class RoomController extends Controller
{
    public function index()
    {
        return response()->json(Room::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:rooms,name|max:255',
            'capacity' => 'nullable|integer|min:1',
        ]);

        $room = Room::create($request->all());
        return response()->json($room, 201);
    }

    public function show(Room $room)
    {
        return response()->json($room);
    }

    public function update(Request $request, Room $room)
    {
        $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('rooms', 'name')->ignore($room->id),
                'max:255',
            ],
            'capacity' => 'nullable|integer|min:1',
        ]);

        $room->update($request->all());
        return response()->json($room);
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return response()->json(null, 204);
    }

    /**
     * Check room availability for a given time range.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAvailability(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date_format:Y-m-d H:i:s',
            'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_time',
        ]);

        $roomId = $request->input('room_id');
        $startTime = Carbon::parse($request->input('start_time'));
        $endTime = Carbon::parse($request->input('end_time'));

        $conflictingEvents = Event::where('room_id', $roomId)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime->subSecond()]) // New event starts within existing
                      ->orWhereBetween('end_time', [$startTime->addSecond(), $endTime]) // New event ends within existing
                      ->orWhere(function ($query) use ($startTime, $endTime) { // Existing event fully contains new
                          $query->where('start_time', '<=', $startTime)
                                ->where('end_time', '>=', $endTime);
                      });
            })
            ->count();

        return response()->json([
            'room_id' => $roomId,
            'start_time' => $startTime->toIso8601String(),
            'end_time' => $endTime->toIso8601String(),
            'is_available' => ($conflictingEvents === 0),
            'conflicting_events_count' => $conflictingEvents,
        ]);
    }
}
