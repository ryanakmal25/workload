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
    protected int|string|array $columnSpan = 4;
    protected static ?string $maxHeight = '400px';

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
                    'data' => $staffs->pluck('tasks_count')->map(fn($count) => (int) $count)->toArray(),
                    'backgroundColor' => $staffs->pluck('color')->toArray(),
                    'borderColor' => $staffs->pluck('color')->toArray(), // samakan dengan background
                    'borderWidth' => 0, // hilangkan border biru
                ],
            ],
            'labels' => $staffs->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false,],
                'tooltip' => [
                    'callbacks' => [
                        'label' => fn($tooltipItem) => (int) $tooltipItem->raw, // tampilkan integer di tooltip
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'ticks' => [
                        'precision' => 0, // pastikan angka bulat di axis
                    ],
                ],
            ],
        ];
    }
}
