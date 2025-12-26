<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Staff;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class StaffTaskChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Task per Staff';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 2;
    protected static ?string $maxHeight = '200px';

    protected function getData(): array
    {
        $start = $this->filters['startDate'] ?? null;
        $end   = $this->filters['endDate'] ?? null;

        if (!$start || !$end || $start > $end) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $staffs = Staff::withCount(['tasks' => function ($query) use ($start, $end) {
            $query->whereBetween('tanggal', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay(),
            ]);
        }])->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Task',
                    'data' => $staffs->pluck('tasks_count')->toArray(),
                    'backgroundColor' => array_fill(0, $staffs->count(), '#60a5fa'),
                ],
            ],
            'labels' => $staffs->pluck('name')->toArray(),
        ];
    }


    protected function getType(): string
    {
        return 'bar';
    }
}
