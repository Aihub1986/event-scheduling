<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index()
    {
        return response()->json(Lesson::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $lesson = Lesson::create($request->all());
        return response()->json($lesson, 201);
    }

    public function show(Lesson $lesson)
    {
        return response()->json($lesson);
    }

    public function update(Request $request, Lesson $lesson)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $lesson->update($request->all());
        return response()->json($lesson);
    }

    public function destroy(Lesson $lesson)
    {
        $lesson->delete();
        return response()->json(null, 204);
    }
}
