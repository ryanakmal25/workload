<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Filament\Resources\TaskResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Support\Enums\MaxWidth;
use Filament\Resources\Components\Tab;

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
            ->paginated([5]) // tampilkan 10 per halaman
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
                Tables\Columns\TextColumn::make('input')
                    ->label('Nama Task'),

                Tables\Columns\TextColumn::make('staff.name')
                    ->label('PIC'),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priority')
                    ->colors([
                        'danger' => 'urgent',
                        'warning' => 'high',
                        'success' => 'medium',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->modalWidth(MaxWidth::Medium)
                    ->form([
                        \Filament\Forms\Components\TextInput::make('task_name')
                            ->label('Item')
                            ->disabled(),

                        \Filament\Forms\Components\Select::make('staff_id')
                            ->label('PIC (Staff)')
                            ->relationship('staff', 'name')
                            ->disabled(),

                        \Filament\Forms\Components\Textarea::make('input')
                            ->label('Project')
                            ->disabled(),

                        \Filament\Forms\Components\Textarea::make('output')
                            ->label('Task')
                            ->disabled(),

                        \Filament\Forms\Components\DatePicker::make('tanggal')
                            ->label('Target Date')
                            ->disabled(),

                        \Filament\Forms\Components\DatePicker::make('tanggal_akhir')
                            ->label('End Date')
                            ->disabled(),

                        \Filament\Forms\Components\TextInput::make('estimasi_jam')
                            ->label('Estimasi Jam')
                            ->disabled(),

                        \Filament\Forms\Components\TextInput::make('status')
                            ->label('Status')
                            ->disabled(),

                        \Filament\Forms\Components\TextInput::make('priority')
                            ->label('Priority')
                            ->disabled(),
                    ]),
            ]);
    }


    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All')
                ->badge($this->getModel()::count()),
        ];

        $statuses = $this->getModel()::query()
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        foreach ($statuses as $status) {

            $slug = str($status)->slug()->toString();
            $label = $this->mapStatusLabel($status);

            $tabs[$slug] = Tab::make($label)
                ->badge(
                    $this->getModel()::where('status', $status)->count()
                )
                ->modifyQueryUsing(
                    fn($query) => $query->where('status', $status)
                );
        }

        return $tabs;
    }
}
