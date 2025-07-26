<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index()
    {
        return response()->json(Teacher::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:teachers,email|max:255',
        ]);

        $teacher = Teacher::create($request->all());
        return response()->json($teacher, 201); // 201 Created
    }

    public function show(Teacher $teacher)
    {
        return response()->json($teacher->load('lessons'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'nullable',
                'email',
                Rule::unique('teachers', 'email')->ignore($teacher->id),
                'max:255',
            ],
        ]);

        $teacher->update($request->all());
        return response()->json($teacher);
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->delete();
        return response()->json(null, 204); // 204 No Content
    }

    /**
     * Get all lessons that a teacher can teach.
     *
     * @param  \App\Models\Teacher  $teacher
     * @return \Illuminate\Http\JsonResponse
     */
    public function lessons(Teacher $teacher)
    {
        $lessons = $teacher->lessons;
        
        return response()->json([
            'teacher' => $teacher,
            'lessons' => $lessons,
        ]);
    }

    /**
     * Attach lessons to a teacher.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Teacher  $teacher
     * @return \Illuminate\Http\JsonResponse
     */
    public function attachLessons(Request $request, Teacher $teacher)
    {
        $request->validate([
            'lesson_ids' => 'required|array',
            'lesson_ids.*' => 'exists:lessons,id',
        ]);

        $teacher->lessons()->attach($request->lesson_ids);

        return response()->json([
            'message' => 'Lessons attached successfully.',
            'teacher' => $teacher->load('lessons'),
        ]);
    }

    /**
     * Detach lessons from a teacher.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Teacher  $teacher
     * @return \Illuminate\Http\JsonResponse
     */
    public function detachLessons(Request $request, Teacher $teacher)
    {
        $request->validate([
            'lesson_ids' => 'required|array',
            'lesson_ids.*' => 'exists:lessons,id',
        ]);

        $teacher->lessons()->detach($request->lesson_ids);

        return response()->json([
            'message' => 'Lessons detached successfully.',
            'teacher' => $teacher->load('lessons'),
        ]);
    }

    /**
     * Sync lessons for a teacher (replace all current lessons with new ones).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Teacher  $teacher
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncLessons(Request $request, Teacher $teacher)
    {
        $request->validate([
            'lesson_ids' => 'required|array',
            'lesson_ids.*' => 'exists:lessons,id',
        ]);

        $teacher->lessons()->sync($request->lesson_ids);

        return response()->json([
            'message' => 'Lessons synced successfully.',
            'teacher' => $teacher->load('lessons'),
        ]);
    }
}
