<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Task;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class ShowGantt extends Component implements HasForms
{
    use InteractsWithForms;

    public array $ganttData = ['data' => [], 'links' => []];
    public ?string $start_date = null;
    public ?string $end_date   = null;
    public ?array $formData = [];
    public ?string $range_type = 'monthly';

    public function mount(): void
    {
        $this->start_date ??= now()->startOfMonth()->format('Y-m-d');
        $this->end_date   ??= now()->endOfMonth()->format('Y-m-d');

        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,
        ]);

        $this->reloadGantt();
    }

    public function render()
    {
        return view('livewire.show-gantt');
    }

    public function reloadGantt(): void
    {
        $start = Carbon::parse($this->start_date)->startOfDay();
        $end   = Carbon::parse($this->end_date)->endOfDay();

        $tasks = Task::query()
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('tanggal', [$start, $end])              // dimulai di range
                    ->orWhereBetween('tanggal_akhir', [$start, $end])      // berakhir di range
                    ->orWhere(function ($q2) use ($start, $end) {          // overlap penuh
                        $q2->where('tanggal', '<', $start)
                            ->where('tanggal_akhir', '>', $end);
                    });
            })
            ->orderBy('staff_id')
            ->orderBy('tanggal')
            ->get()
            ->groupBy('staff_id');

        $this->ganttData = [
            'data'  => $this->buildGanttData($tasks),
            'links' => [],
        ];

        $this->dispatch(
            'refresh-gantt',
            ganttData: $this->ganttData,
            startDate: $this->start_date,
            endDate: $this->end_date
        );
    }


    /** Buat daftar tanggal dalam rentang filter */
    protected function getDateRange(): array
    {
        $dates = [];
        $start = Carbon::parse($this->start_date);
        $end   = Carbon::parse($this->end_date);
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dates[] = $d->format('Y-m-d');
        }
        return $dates;
    }

    /** Inisialisasi kapasitas harian (8 jam kerja, 0 weekend) */
    protected function initCapacity(array $dates): array
    {
        $capacity = [];
        foreach ($dates as $date) {
            $capacity[$date] = Carbon::parse($date)->isWeekend() ? 0 : 8;
        }
        return $capacity;
    }

    /** Kurangi kapasitas harian dengan long term project */
    protected function applyLongTermAllocation(array &$capacity, Task $task): void
    {
        $start = Carbon::parse($task->tanggal);
        $end   = Carbon::parse($task->tanggal_akhir);
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if ($d->isWeekend()) continue;
            $date = $d->format('Y-m-d');
            if (isset($capacity[$date])) {
                $allocation = max(0, (int) ($task->allocation_hours ?? 0));
                $capacity[$date] = max(0, $capacity[$date] - $allocation);
            }
        }
    }

    /** Jadwalkan task non long term berdasarkan sisa kapasitas */
    protected function scheduleTask(array &$capacity, array $dates, Task $task): int
    {
        $remaining = (int) ($task->estimasi_jam ?? 0);
        $startDate = Carbon::parse($task->tanggal)->format('Y-m-d');
        $dayIndex  = array_search($startDate, $dates, true);

        $daysUsed = 0;
        while ($remaining > 0 && $dayIndex !== false && $dayIndex < count($dates)) {
            $date = $dates[$dayIndex];
            if (Carbon::parse($date)->isWeekend()) {
                $dayIndex++;
                continue;
            }

            $available = $capacity[$date] ?? 0;
            if ($available > 0) {
                $consumed = min($available, $remaining);
                $capacity[$date] -= $consumed;
                $remaining -= $consumed;
                $daysUsed++;
            }
            $dayIndex++;
        }

        return max(1, $daysUsed);
    }

    protected function buildGanttData($grouped): array
    {
        $dates = $this->getDateRange();
        $rows  = [];

        foreach ($grouped as $staffId => $tasks) {
            $staff = $tasks->first()->staff;

            // Parent node
            $rows[] = [
                'id'    => "staff_{$staffId}",
                'text'  => $staff->name,
                'type'  => 'project',
                'open'  => true,
                'color' => $staff->color,
            ];

            $capacity = $this->initCapacity($dates);

            // Kurangi kapasitas dengan long term project
            foreach ($tasks as $task) {
                if ($task->is_long_term && $task->tanggal && $task->tanggal_akhir) {
                    $this->applyLongTermAllocation($capacity, $task);
                }
            }

            // Tambahkan task
            foreach ($tasks as $task) {
                $startDate = Carbon::parse($task->tanggal)->format('Y-m-d');
                $duration  = $task->is_long_term && $task->tanggal && $task->tanggal_akhir
                    ? $this->countWorkingDays($task->tanggal, $task->tanggal_akhir)
                    : $this->scheduleTask($capacity, $dates, $task);

                $progress = match ($task->status) {
                    'todo'     => 0,
                    'progress' => 0.5,
                    'done'     => 1,
                    default    => 0,
                };

                $rows[] = [
                    'id'         => $task->id,
                    'parent'     => "staff_{$staffId}",
                    'text'       => $task->input,
                    'start_date' => $startDate,
                    'duration'   => $duration,
                    'progress'   => $progress,
                    'staff_id'   => $staffId,
                    'staff_name' => $staff->name,
                    'color'      => $staff->color,
                    'open'       => true,
                    'priority'   => $task->priority,
                ];
            }
        }

        return $rows;
    }

    /** Hitung jumlah hari kerja antara dua tanggal */
    protected function countWorkingDays(string $start, string $end): int
    {
        $count = 0;
        for ($d = Carbon::parse($start)->copy(); $d->lte(Carbon::parse($end)); $d->addDay()) {
            if (!$d->isWeekend()) $count++;
        }
        return $count;
    }

    public function previousRange(): void
    {
        if ($this->range_type === 'weekly') {
            $this->start_date = Carbon::parse($this->start_date)->subWeek()->startOfWeek()->format('Y-m-d');
            $this->end_date   = Carbon::parse($this->end_date)->subWeek()->endOfWeek()->format('Y-m-d');
        }

        if ($this->range_type === 'monthly') {
            $this->start_date = Carbon::parse($this->start_date)->subMonth()->startOfMonth()->format('Y-m-d');
            $this->end_date   = Carbon::parse($this->end_date)->subMonth()->endOfMonth()->format('Y-m-d');
        }

        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,
        ]);

        $this->reloadGantt();
    }

    public function nextRange(): void
    {
        if ($this->range_type === 'weekly') {
            $this->start_date = Carbon::parse($this->start_date)->addWeek()->startOfWeek()->format('Y-m-d');
            $this->end_date   = Carbon::parse($this->end_date)->addWeek()->endOfWeek()->format('Y-m-d');
        }

        if ($this->range_type === 'monthly') {
            $this->start_date = Carbon::parse($this->start_date)->addMonth()->startOfMonth()->format('Y-m-d');
            $this->end_date   = Carbon::parse($this->end_date)->addMonth()->endOfMonth()->format('Y-m-d');
        }

        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,
        ]);

        $this->reloadGantt();
    }
    public function setRange(string $type): void
    {
        $this->range_type = $type;

        if ($type === 'weekly') {
            $this->start_date = now()->startOfWeek()->format('Y-m-d');
            $this->end_date   = now()->endOfWeek()->format('Y-m-d');
        }

        if ($type === 'monthly') {
            $this->start_date = now()->startOfMonth()->format('Y-m-d');
            $this->end_date   = now()->endOfMonth()->format('Y-m-d');
        }

        // Update form agar datepicker ikut berubah
        // $this->form->fill([
        //     'start_date' => $this->start_date,
        //     'end_date'   => $this->end_date,
        // ]);

        $this->reloadGantt();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('start_date')
                    ->label("Start Date")
                    ->format('Y-m-d')
                    ->default(now()->startOfMonth()->format('Y-m-d'))
                    ->required()
                    ->live()
                    ->disabled()
                    ->afterStateUpdated(fn($state) => $this->updateFilter('start_date', $state)),

                Forms\Components\DatePicker::make('end_date')
                    ->label("End Date")
                    ->format('Y-m-d')
                    ->default(now()->endOfMonth()->format('Y-m-d'))
                    ->required()
                    ->live()
                    ->disabled()
                    ->afterStateUpdated(fn($state) => $this->updateFilter('end_date', $state)),
            ])
            ->statePath('formData')
            ->columns(3);
    }

    protected function updateFilter(string $field, string $value): void
    {
        $this->$field = $value;
        $this->reloadGantt();
    }
}
