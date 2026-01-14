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
    protected int|string|array $columnSpan = 8;
    protected static ?string $minHeight = '500px';

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

        $opened    = (clone $query)->where('status', 'opened')->count();
        $progress  = (clone $query)->where('status', 'progress')->count();
        $closed    = (clone $query)->where('status', 'closed')->count();
        $overdue   = (clone $query)->where('status', 'overdue')->count();
        $postponed = (clone $query)->where('status', 'postponed')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Tasks',
                    'data' => [
                        $opened,
                        $progress,
                        $closed,
                        $overdue,
                        $postponed,
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
                "Opened ({$opened})",
                "Progress ({$progress})",
                "Closed ({$closed})",
                "Overdue ({$overdue})",
                "Postponed ({$postponed})",
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
            'responsive' => false,
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => fn($tooltipItem) => (int) $tooltipItem->raw, // tampilkan integer di tooltip
                    ],
                ],
            ],
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
        ];
    }
}
