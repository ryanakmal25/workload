<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class PriorityTaskTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 6;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Priority Task')
            ->striped()
            ->paginated([5])
            ->query(function () {
                $start = $this->filters['startDate'] ?? null;
                $end = $this->filters['endDate'] ?? null;

                $query = Task::query()
                    ->whereIn('priority', ['urgent', 'high', 'medium'])
                    ->orderBy('tanggal', 'desc');

                if ($start && $end && $start <= $end) {
                    $query->whereBetween('tanggal', [$start, $end]);
                }

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('task_name')
                    ->label('Nama Task'),
                   
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff'),
                    
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->colors([
                        'danger' => 'urgent',
                        'warning' => 'high',
                        'success' => 'medium',
                    ]),
            ]);
    }
}
