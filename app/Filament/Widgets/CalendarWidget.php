<?php

namespace App\Filament\Widgets;
use Saade\FilamentFullCalendar\Actions\CreateAction;
use App\Filament\Resources\EventResource;
use App\Models\Event;
use App\Models\Lesson;
use App\Models\Room;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class CalendarWidget extends FullCalendarWidget
{
        public Model | string | null $model = Event::class;
protected function headerActions(): array
 {
     return [
         CreateAction::make()
             ->mountUsing(
                 function (Form $form, array $arguments) {
                     $form->fill([
                         'start_time' => $arguments['start'] ?? null,
                         'end_time' => $arguments['end'] ?? null
                     ]);
                 }
             )->mutateFormDataUsing(function (array $data): array {
               return $data;

             })   ->action(function (array $data, Action $action) {
                //   try {


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
                    ->body('This room is already reserved for another event at the selected time. the conflicted event starts at '. $conflictingEvent->start_time->format('Y-m-d H:i') . ' and ends at ' . $conflictingEvent->end_time->format('Y-m-d H:i'))
                    ->danger()
                    ->send();
                $action->halt();
                return; // Stop execution
            }

            // Create the event if no conflict is found
            Event::create($data);

            Notification::make()
                ->title('Event created successfully!')
                ->success()
                ->send();



        // } catch (\Exception $e) {

        //     Notification::make()
        //         ->title('An error occurred')
        //         ->body($e->getMessage())
        //         ->danger()
        //         ->send();
        // }
            })
     ];
 }
   protected function modalActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function viewAction(): Action
    {
        return ViewAction::make();
    }

          public function getFormSchema(): array
    {
        return [
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
                    ->displayFormat('Y-m-d H:i'),

                DateTimePicker::make('end_time')
                    ->required()
                    ->native(false)
                    ->seconds(false)
                    ->displayFormat('Y-m-d H:i')
                    ->after('start_time') // Ensures end time is after start time.
        ];
    }

    /**
     * FullCalendar will call this function whenever it needs new event data.
     * This is triggered when the user clicks prev/next or switches views on the calendar.
     */
    public function fetchEvents(array $fetchInfo): array
    {
          return Event::with(['lesson', 'teacher', 'room'])
            ->get()
            ->map(function (Event $event) {
                return [
                    'id' => $event->id,
                    'title' => $event->lesson->name . ' with ' . $event->teacher->name,
                    'start' => $event->start_time->toIso8601String(),
                    'end' => $event->end_time->toIso8601String(),
                    'url' => EventResource::getUrl(name: 'view', parameters: ['record' => $event]),
                    'shouldOpenUrlInNewTab' => true,
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
}
