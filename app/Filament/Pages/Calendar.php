<?php

namespace App\Filament\Pages;

use App\Models\Event;
use App\Models\Lesson;
use App\Models\Room;
use App\Models\Teacher;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Calendar extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static string $view = 'filament.pages.calendar';
    protected static ?string $navigationGroup = 'Scheduling';
    protected static ?int $navigationSort = 1;

    // This holds the form data, linked via statePath('data') in the form method.
    public ?array $data = [];

    // This property will store the date clicked on the calendar to pre-fill the form.
    public ?string $selectedDate = null;

    public function mount(): void
    {
        // Initialize the form with empty/default values.
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('lesson_id')
                    ->label('Lesson')
                    ->options(Lesson::all()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('teacher_id')
                    ->label('Teacher')
                    ->options(Teacher::all()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('room_id')
                    ->label('Room')
                    ->options(Room::all()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                DateTimePicker::make('start_time')
                    ->required()
                    ->native(false)
                    ->seconds(false)
                    ->displayFormat('Y-m-d H:i')
                    // FIX: Removed invalid rule ->afterOrEqual('selectedDate').
                    // The default value correctly sets the date based on the user's click.
                    ->default(fn() => $this->selectedDate ? Carbon::parse($this->selectedDate)->setTime(9, 0, 0) : null),
                DateTimePicker::make('end_time')
                    ->required()
                    ->native(false)
                    ->seconds(false)
                    ->displayFormat('Y-m-d H:i')
                    ->after('start_time') // Ensures end time is after start time.
                    ->default(fn() => $this->selectedDate ? Carbon::parse($this->selectedDate)->setTime(10, 0, 0) : null),
            ])
            ->statePath('data'); // Binds form data to $this->data
    }

    /**
     * Fetches and formats events for FullCalendar.
     * This MUST always return an array.
     */
    public function getEvents(): array
    {
        // Eager load relationships to prevent N+1 query issues.
        return Event::with(['lesson', 'teacher', 'room'])
            ->get()
            ->map(function (Event $event) {
                return [
                    'id' => $event->id,
                    'title' => $event->lesson->name . ' with ' . $event->teacher->name,
                    'start' => $event->start_time->toIso8601String(),
                    'end' => $event->end_time->toIso8601String(),
                    'extendedProps' => [
                        'roomName' => $event->room->name,
                        'roomId' => $event->room_id,
                        'lessonId' => $event->lesson_id,
                        'teacherId' => $event->teacher_id,
                    ],
                    // Example styling
                    'backgroundColor' => '#3b82f6', // Tailwind blue-500
                    'borderColor' => '#2563eb',     // Tailwind blue-600
                ];
            })
            ->toArray();

    }

    /**
     * Called from Alpine.js when a date is clicked to open the creation modal.
     */
    public function openCreateModal(string $date): void
    {
        $this->selectedDate = $date;
        // Re-fill the form to apply the new default start/end times based on the clicked date.
        $this->form->fill([
            'start_time' => Carbon::parse($date)->setTime(9, 0, 0),
            'end_time' => Carbon::parse($date)->setTime(10, 0, 0),
        ]);
        $this->dispatch('open-modal', id: 'createEventModal');
    }

    /**
     * Handles the event creation form submission.
     */
    public function createEvent(): void
    {
        try {
            $data = $this->form->getState();

            $startTime = Carbon::parse($data['start_time']);
            $endTime = Carbon::parse($data['end_time']);
            $roomId = $data['room_id'];

            // --- Robust Room Conflict Check ---
            // This query checks for any existing event in the same room that overlaps
            // with the new event's time range.
            $conflictingEvent = Event::where('room_id', $roomId)
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where('start_time', '<', $endTime) // An existing event starts before the new one ends
                          ->where('end_time', '>', $startTime);   // AND it ends after the new one starts.
                })
                ->first();

            if ($conflictingEvent) {
                Notification::make()
                    ->title('Room Conflict')
                    ->body('This room is already reserved for another event at the selected time.')
                    ->danger()
                    ->send();
                return; // Stop execution
            }

            // Create the event if no conflict is found
            Event::create($data);

            Notification::make()
                ->title('Event created successfully!')
                ->success()
                ->send();

            $this->form->fill(); // Clear form for the next entry
            $this->dispatch('close-modal', id: 'createEventModal');

            // CRITICAL FIX: Dispatch 'refresh-calendar' WITH the updated event data.
            // The frontend is listening for this payload.
            $this->dispatch('refresh-calendar', events: $this->getEvents());

        } catch (\Exception $e) {
            Notification::make()
                ->title('An error occurred')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
