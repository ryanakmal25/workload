<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Task;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TaskChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Status Task';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 1;
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

        $query = Task::query()
            ->whereBetween('tanggal', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay(),
            ]);

        return [
            'datasets' => [
                [
                    'label' => 'Tasks',
                    'data' => [
                        (clone $query)->where('status', 'opened')->count(),
                        (clone $query)->where('status', 'progress')->count(),
                        (clone $query)->where('status', 'closed')->count(),
                        (clone $query)->where('status', 'overdue')->count(),
                        (clone $query)->where('status', 'postponed')->count(),
                    ],
                    'backgroundColor' => [
                        '#60a5fa', // Opened
                        '#facc15', // Progress
                        '#4ade80', // Closed
                        '#f87171', // Overdue
                        '#928aadff', // Postponed
                    ],
                ],
            ],
            'labels' => [
                'Opened',
                'Progress',
                'Closed',
                'Overdue',
                'Postponed',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
        ];
    }
}
