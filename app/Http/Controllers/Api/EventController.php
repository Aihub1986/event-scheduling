<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function index()
    {
        // Eager load relationships for better performance
        return response()->json(Event::with(['lesson', 'teacher', 'room'])->get());
    }

    public function store(Request $request)
    {
        // This is a basic store, the 'schedule' method below has conflict logic.
        $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
            'teacher_id' => 'required|exists:teachers,id',
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date_format:Y-m-d H:i:s',
            'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_time',
        ]);

        $event = Event::create($request->all());
        return response()->json($event, 201);
    }

    public function show(Event $event)
    {
        return response()->json($event->load(['lesson', 'teacher', 'room']));
    }

    public function update(Request $request, Event $event)
    {
        $request->validate([
            'lesson_id' => 'sometimes|required|exists:lessons,id',
            'teacher_id' => 'sometimes|required|exists:teachers,id',
            'room_id' => 'sometimes|required|exists:rooms,id',
            'start_time' => 'sometimes|required|date_format:Y-m-d H:i:s',
            'end_time' => 'sometimes|required|date_format:Y-m-d H:i:s|after:start_time',
        ]);

        // When updating, also check for conflicts if room or time changes
        if ($request->has(['room_id', 'start_time', 'end_time'])) {
            $this->checkAndPreventConflict(
                $request->input('room_id'),
                Carbon::parse($request->input('start_time')),
                Carbon::parse($request->input('end_time')),
                $event->id // Pass current event ID to ignore it in conflict check
            );
        } elseif ($request->has('room_id') || $request->has('start_time') || $request->has('end_time')) {
            // If only one of the time/room fields is updated, use existing values for others
            $roomId = $request->input('room_id', $event->room_id);
            $startTime = Carbon::parse($request->input('start_time', $event->start_time));
            $endTime = Carbon::parse($request->input('end_time', $event->end_time));
            $this->checkAndPreventConflict($roomId, $startTime, $endTime, $event->id);
        }


        $event->update($request->all());
        return response()->json($event);
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json(null, 204);
    }

    /**
     * Schedule a new event with room availability check.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function schedule(Request $request)
    {
        $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
            'teacher_id' => 'required|exists:teachers,id',
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date_format:Y-m-d H:i:s',
            'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_time',
        ]);

        $roomId = $request->input('room_id');
        $startTime = Carbon::parse($request->input('start_time'));
        $endTime = Carbon::parse($request->input('end_time'));

        // Perform the room conflict check
        $this->checkAndPreventConflict($roomId, $startTime, $endTime);

        // If no conflict, create the event
        $event = Event::create($request->all());

        return response()->json([
            'message' => 'Event scheduled successfully.',
            'event' => $event->load(['lesson', 'teacher', 'room']),
        ], 201);
    }

    /**
     * Reschedule an existing event with conflict checking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function reschedule(Request $request, Event $event)
    {
        $request->validate([
            'start_time' => 'required|date_format:Y-m-d H:i:s',
            'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_time',
            'room_id' => 'sometimes|exists:rooms,id',
        ]);

        $roomId = $request->input('room_id', $event->room_id);
        $startTime = Carbon::parse($request->input('start_time'));
        $endTime = Carbon::parse($request->input('end_time'));

        // Check for conflicts with the new time/room
        $this->checkAndPreventConflict($roomId, $startTime, $endTime, $event->id);

        // Update the event
        $event->update([
            'start_time' => $startTime,
            'end_time' => $endTime,
            'room_id' => $roomId,
        ]);

        return response()->json([
            'message' => 'Event rescheduled successfully.',
            'event' => $event->load(['lesson', 'teacher', 'room']),
        ]);
    }

    /**
     * Get all events for a specific teacher.
     *
     * @param  \App\Models\Teacher  $teacher
     * @return \Illuminate\Http\JsonResponse
     */
    public function teacherEvents(Teacher $teacher)
    {
        $events = $teacher->events()
            ->with(['lesson', 'room'])
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'teacher' => $teacher,
            'events' => $events,
        ]);
    }

    /**
     * Helper method to check for room conflicts and throw an exception if found.
     *
     * @param int $roomId
     * @param Carbon $startTime
     * @param Carbon $endTime
     * @param int|null $ignoreEventId Optional ID of event to ignore (for updates)
     * @throws ValidationException
     */
    protected function checkAndPreventConflict(int $roomId, Carbon $startTime, Carbon $endTime, ?int $ignoreEventId = null): void
    {
        $query = Event::where('room_id', $roomId)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime->subSecond()])
                      ->orWhereBetween('end_time', [$startTime->addSecond(), $endTime])
                      ->orWhere(function ($query) use ($startTime, $endTime) {
                          $query->where('start_time', '<=', $startTime)
                                ->where('end_time', '>=', $endTime);
                      });
            });

        if ($ignoreEventId) {
            $query->where('id', '!=', $ignoreEventId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'room_id' => ['This room is already reserved for another event at the selected time. Please choose a different room or time.'],
            ]);
        }
    }
}
