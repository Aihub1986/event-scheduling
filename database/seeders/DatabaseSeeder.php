<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Teacher;
use App\Models\Lesson;
use App\Models\Room;
use App\Models\Event;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create Teachers
        $teachers = [
            ['name' => 'John Smith', 'email' => 'john.smith@school.com'],
            ['name' => 'Sarah Johnson', 'email' => 'sarah.johnson@school.com'],
            ['name' => 'Michael Brown', 'email' => 'michael.brown@school.com'],
            ['name' => 'Emily Davis', 'email' => 'emily.davis@school.com'],
        ];

        foreach ($teachers as $teacherData) {
            Teacher::create($teacherData);
        }

        // Create Lessons
        $lessons = [
            ['name' => 'Mathematics', 'description' => 'Advanced mathematics course'],
            ['name' => 'Physics', 'description' => 'Physics fundamentals'],
            ['name' => 'Chemistry', 'description' => 'Chemistry laboratory course'],
            ['name' => 'Biology', 'description' => 'Biology and life sciences'],
            ['name' => 'English Literature', 'description' => 'English literature and composition'],
            ['name' => 'History', 'description' => 'World history course'],
        ];

        foreach ($lessons as $lessonData) {
            Lesson::create($lessonData);
        }

        // Create Rooms
        $rooms = [
            ['name' => 'Room 101', 'capacity' => 30],
            ['name' => 'Room 102', 'capacity' => 25],
            ['name' => 'Room 103', 'capacity' => 35],
            ['name' => 'Laboratory A', 'capacity' => 20],
            ['name' => 'Laboratory B', 'capacity' => 20],
        ];

        foreach ($rooms as $roomData) {
            Room::create($roomData);
        }

        // Attach teachers to lessons (many-to-many relationship)
        $teacherLessonAssignments = [
            // John Smith can teach Math and Physics
            [1, 1], [1, 2],
            // Sarah Johnson can teach Chemistry and Biology
            [2, 3], [2, 4],
            // Michael Brown can teach Math and Chemistry
            [3, 1], [3, 3],
            // Emily Davis can teach English and History
            [4, 5], [4, 6],
        ];

        foreach ($teacherLessonAssignments as [$teacherId, $lessonId]) {
            $teacher = Teacher::find($teacherId);
            $lesson = Lesson::find($lessonId);
            $teacher->lessons()->attach($lesson);
        }

        // Create some sample events
        $events = [
            [
                'lesson_id' => 1,
                'teacher_id' => 1,
                'room_id' => 1,
                'start_time' => now()->addDays(1)->setTime(9, 0),
                'end_time' => now()->addDays(1)->setTime(10, 30),
            ],
            [
                'lesson_id' => 3,
                'teacher_id' => 2,
                'room_id' => 4,
                'start_time' => now()->addDays(1)->setTime(11, 0),
                'end_time' => now()->addDays(1)->setTime(12, 30),
            ],
            [
                'lesson_id' => 5,
                'teacher_id' => 4,
                'room_id' => 2,
                'start_time' => now()->addDays(2)->setTime(14, 0),
                'end_time' => now()->addDays(2)->setTime(15, 30),
            ],
        ];

        foreach ($events as $eventData) {
            Event::create($eventData);
        }
    }
}
