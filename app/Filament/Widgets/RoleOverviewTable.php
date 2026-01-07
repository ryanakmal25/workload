<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\MaxWidth;

class RoleOverviewTable extends BaseWidget
{
    protected int|string|array $columnSpan = 12;
    protected static ?int $sort = 5;

    // terima role_id dari luar
    public ?int $roleId = null;

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Task::query()
                    ->with('staff')
                    ->when($this->roleId, fn (Builder $q) => 
                        $q->whereHas('staff', fn ($q2) => $q2->where('role_id', $this->roleId))
                    )
            )
            ->columns([
                Tables\Columns\TextColumn::make('staff.name')->label('PIC'),
                Tables\Columns\TextColumn::make('task_name')->label('Item'),
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'danger' => 'urgent',
                        'warning' => 'high',
                        'info' => 'medium',
                        'success' => 'low',
                        'gray' => 'not_priority',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'opened',
                        'warning' => 'progress',
                        'success' => 'closed',
                        'danger' => 'overdue',
                        'cyan' => 'postponed',
                    ]),
                Tables\Columns\TextColumn::make('progress')
                    ->label('%')
                    ->formatStateUsing(fn ($state) => $state !== null ? $state . '%' : '-'),
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
                            ->label('Input')
                            ->disabled(),

                        \Filament\Forms\Components\Textarea::make('output')
                            ->label('Output')
                            ->disabled(),

                        \Filament\Forms\Components\DatePicker::make('tanggal')
                            ->label('Target Date')
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

                        \Filament\Forms\Components\TextInput::make('progress')
                            ->label('%')
                            ->disabled(),
                    ]),
            ])
            ->paginated(10); // pagination 10
    }
}
