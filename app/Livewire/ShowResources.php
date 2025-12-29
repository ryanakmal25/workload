<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Task;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class ShowResources extends Component implements HasForms
{
    use InteractsWithForms;

    public array $resourcesData = [];

    public ?string $start_date = null;
    public ?string $end_date   = null;
    public ?array $formData = [];
    public ?string $range_type = 'monthly';

    public function mount(): void
    {
        $this->start_date = $this->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date   = $this->end_date   ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,
        ]);

        $this->reloadResources();
    }

    public function render()
    {
        return view('livewire.show-resources');
    }

    public function reloadResources(): void
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
            ->get();

        // Buat daftar tanggal (semua hari termasuk weekend)
        $dates = collect(Carbon::parse($this->start_date)->daysUntil(Carbon::parse($this->end_date)))
            ->map(fn($d) => $d->format('Y-m-d'))
            ->toArray();

        $resources = [];

        foreach ($tasks->groupBy('staff_id') as $staffId => $staffTasks) {
            $staff = $staffTasks->first()->staff;
            $row = [
                'name' => $staff->name,
                'workload' => 0,
                'days' => array_fill_keys($dates, 0),
            ];

            foreach ($staffTasks as $task) {
                if ($task->is_long_term && $task->tanggal && $task->tanggal_akhir) {
                    $period = Carbon::parse($task->tanggal)->daysUntil(Carbon::parse($task->tanggal_akhir));
                    foreach ($period as $day) {
                        if ($day->isWeekend()) continue;
                        $date = $day->format('Y-m-d');
                        if (isset($row['days'][$date])) {
                            $row['days'][$date] += $task->allocation_hours ?? 0;
                            $row['workload'] += $task->allocation_hours ?? 0;
                        }
                    }
                } else {
                    $carbonDate = Carbon::parse($task->tanggal);
                    if ($carbonDate->isWeekend()) continue;
                    $date = $carbonDate->format('Y-m-d');
                    if (isset($row['days'][$date])) {
                        $row['days'][$date] += $task->estimasi_jam ?? 0;
                        $row['workload'] += $task->estimasi_jam ?? 0;
                    }
                }
            }

            $resources[] = $row;
        }

        $this->resourcesData = [
            'dates' => $dates,
            'rows' => $resources,
        ];
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
        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,
        ]);

        $this->reloadResources();
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

        $this->reloadResources();
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

        $this->reloadResources();
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
                    ->afterStateUpdated(function ($state) {
                        $this->start_date = $state;
                        $this->reloadResources();
                    }),

                Forms\Components\DatePicker::make('end_date')
                    ->label("End Date")
                    ->format('Y-m-d')
                    ->default(now()->endOfMonth()->format('Y-m-d'))
                    ->required()
                    ->live()
                    ->disabled()
                    ->afterStateUpdated(function ($state) {
                        $this->end_date = $state;
                        $this->reloadResources();
                    }),
            ])
            ->statePath('formData')
            ->columns(2);
    }
}
