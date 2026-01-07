<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

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
            ])
            ->paginated(10); // pagination 10
    }
}
