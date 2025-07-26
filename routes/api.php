<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\TeacherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (e.g., for login)
Route::post('/auth/login', [AuthController::class, 'login']);
// CRUD for Teachers
    Route::apiResource('teachers', TeacherController::class);

    // CRUD for Lessons
    Route::apiResource('lessons', LessonController::class);

    // CRUD for Rooms
    Route::apiResource('rooms', RoomController::class);

    // CRUD for Events
    Route::apiResource('events', EventController::class);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });


    // Custom API for Event Scheduling with Conflict Check
    Route::post('/events/schedule', [EventController::class, 'schedule']);

    // Custom API for Event Rescheduling
    Route::patch('/events/{event}/reschedule', [EventController::class, 'reschedule']);

    // Custom API for getting events for a specific teacher
    Route::get('/teachers/{teacher}/events', [EventController::class, 'teacherEvents']);

    // Teacher-Lesson relationship management
    Route::get('/teachers/{teacher}/lessons', [TeacherController::class, 'lessons']);
    Route::post('/teachers/{teacher}/lessons/attach', [TeacherController::class, 'attachLessons']);
    Route::post('/teachers/{teacher}/lessons/detach', [TeacherController::class, 'detachLessons']);
    Route::put('/teachers/{teacher}/lessons/sync', [TeacherController::class, 'syncLessons']);

    // Custom API for Room Availability Check
    Route::get('/rooms/check-availability', [RoomController::class, 'checkAvailability']);

    // Logout
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
