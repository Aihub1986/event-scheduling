<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
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
        return response()->json($teacher);
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
}
