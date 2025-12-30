<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Models\Task;
use Carbon\CarbonPeriod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Exports\TasksExporter;
use App\Filament\Imports\TasksImporter;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\ExportAction as TablesActionsExportAction;
use Filament\Tables\Actions\ImportAction as TablesActionsImportAction;
use Filament\Notifications\Notification;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Illuminate\Database\Eloquent\Model;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        Task::where('is_long_term', true)
            ->whereNotIn('status', ['closed', 'postponed', 'opened'])
            ->whereDate('tanggal_akhir', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        Task::where('is_long_term', false)
            ->whereNotIn('status', ['closed', 'postponed', 'opened'])
            ->whereDate('tanggal', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        return $query;
    }



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make('Task')
                    ->schema([
                        Forms\Components\TextInput::make('task_name')
                            ->label('Item')
                            ->required()
                            ->maxLength(100),

                        // Checkbox Long Term Project di bawah Task Name
                        Forms\Components\Checkbox::make('is_long_term')
                            ->label('Long Term Project')
                            ->reactive(),

                        Forms\Components\Select::make('staff_id')
                            ->relationship('staff', 'name')
                            ->label('PIC')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\Select::make('role_id')
                                    ->relationship('role', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nama Role')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Deskripsi Role')
                                            ->required()
                                            ->maxLength(255),
                                    ]),
                                Forms\Components\ColorPicker::make('color'),
                            ]),

                        Forms\Components\Textarea::make('input')
                            ->label('Input')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('output')
                            ->maxLength(255),

                        // Jika bukan long term → tampilkan tanggal & estimasi jam
                        Forms\Components\DatePicker::make('tanggal')
                            ->label(function (Get $get) {
                                return match ($get('is_long_term')) {
                                    true => 'Start Date Project',
                                    default => 'Target'
                                };
                            })
                            ->closeOnDateSelection()
                            ->disabledDates(function () {
                                $start = now()->startOfMonth();
                                $end   = now()->addMonths(6)->endOfMonth();
                                $period = CarbonPeriod::create($start, $end);

                                $weekends = [];

                                foreach ($period as $date) {
                                    if ($date->isWeekend()) {
                                        $weekends[] = $date->format('Y-m-d');
                                    }
                                }

                                return $weekends;
                            })
                            ->native(false)
                            ->reactive()
                            ->required(),

                        Forms\Components\TextInput::make('estimasi_jam')
                            ->numeric()
                            ->visible(fn(Get $get) => !$get('is_long_term'))
                            ->required(fn(Get $get) => !$get('is_long_term')),

                        Forms\Components\DatePicker::make('tanggal_akhir')
                            ->label('End Date Project')
                            ->closeOnDateSelection()
                            ->disabledDates(function () {
                                $start = now()->startOfMonth();
                                $end   = now()->addMonths(6)->endOfMonth();
                                $period = CarbonPeriod::create($start, $end);

                                $weekends = [];

                                foreach ($period as $date) {
                                    if ($date->isWeekend()) {
                                        $weekends[] = $date->format('Y-m-d');
                                    }
                                }

                                return $weekends;
                            })
                            ->native(false)
                            ->visible(fn(Get $get) => $get('is_long_term'))
                            ->required(fn(Get $get) => $get('is_long_term')),

                        // Jika long term → tampilkan alokasi jam + start/end date
                        Forms\Components\TextInput::make('allocation_hours')
                            ->label('Alokasi Jam per Hari')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(7)
                            ->visible(fn(Get $get) => $get('is_long_term'))
                            ->required(fn(Get $get) => $get('is_long_term')),

                        Forms\Components\Select::make('status')
                            ->label('Evaluasi Efektivitas')
                            ->required()
                            ->native(false)
                            ->options([
                                'opened' => 'Opened',
                                'progress' => 'Progress',
                                'closed' => 'Closed',
                                'overdue' => 'Overdue',
                                'postponed' => 'Postponed',
                            ]),


                        Forms\Components\Select::make('priority')
                            ->required()
                            ->native(false)
                            ->options([
                                'urgent' => 'Urgent',
                                'high' => 'High',
                                'medium' => 'Medium',
                                'low' => 'Low',
                                'not_priority' => 'Not Priority',
                            ])
                            ->default('not_priority'),
                    ])
                    ->columns(1)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->heading('Status')
            // ->header(view('tables.legend'))

            ->columns([
                Tables\Columns\TextColumn::make('task_name')
                    ->label('Item')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextInputColumn::make('input')
                    ->label('Input')
                    ->tooltip(fn (Model $record): string => "{$record->input}")
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextInputColumn::make('output')
                    ->label('Output')
                    ->tooltip(fn (Model $record): string => "{$record->output}")
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('staff.name')
                    ->label('PIC')
                    ->searchable(),

                // Indikator long term
                Tables\Columns\TextColumn::make('is_long_term')
                    ->label('Long Term')
                    ->Badge()
                    ->color(fn($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Jika bukan long term → tampilkan tanggal & estimasi jam
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Target')
                    ->date()
                    ->sortable()
                    ->hidden(fn($record) => $record?->is_long_term)
                    ->toggleable(isToggledHiddenByDefault: false),
                    

                Tables\Columns\TextColumn::make('tanggal_akhir')
                    ->label('End Date')
                    ->placeholder('0')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->hidden(fn($record) => $record?->is_long_term),

                Tables\Columns\TextColumn::make('estimasi_jam')
                    ->label('Workload')
                    ->placeholder('0')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->hidden(fn($record) => $record?->is_long_term),

                Tables\Columns\TextColumn::make('allocation_hours')
                    ->label('Alokasi jam')
                    ->formatStateUsing(fn($state) => $state ?? 0) // kalau null → 0
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->hidden(fn($record) => $record?->is_long_term),


                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->Badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->colors([
                        'danger' => 'urgent',
                        'warning' => 'high',
                        'info' => 'medium',
                        'success' => 'low',
                        'teal' => 'not_priority',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'urgent' => 'Urgent',
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                        'not_priority' => 'Not Priority',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Evaluasi Efektivitas')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'opened' => 'Opened',
                        'progress' => 'Progress',
                        'closed' => 'Closed',
                        'overdue' => 'Overdue',
                        'postponed' => 'Postponed',
                        default => ucfirst($state),
                    })

                    ->colors([
                        'primary' => 'opened',
                        'warning' => 'progress',
                        'success' => 'closed',
                        'danger'  => 'overdue',
                        'cyan'    => 'postponed', // status "postponed" pakai warna biru (#000080)
                    ]),


                Tables\Columns\TextColumn::make('total_overdue')
                    ->label('Total Overdue (hari)')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'danger' : 'zinc')
                    ->toggleable(isToggledHiddenByDefault: false),



            ])

            ->filters([
                Tables\Filters\SelectFilter::make('staff.name')
                    ->relationship('staff', 'name')
                    ->label('PIC')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'urgent'       => 'Urgent',
                        'high'         => 'High',
                        'medium'       => 'Medium',
                        'low'          => 'Low',
                        'not_priority' => 'Not Priority',
                    ])
                    ->multiple(),

                DateRangeFilter::make('tanggal')
                    ->label('Tanggal Project'),

                Tables\Filters\SelectFilter::make('is_long_term')
                    ->label('Long Term')
                    ->options([
                        true  => 'Yes',
                        false => 'No',
                    ]),
            ])


            ->headerActions([
                Tables\Actions\Action::make('legend')
                    ->label('Petunjuk Warna')
                    ->view('tables.legend'),
            ])

            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->modalWidth(MaxWidth::Small),

                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->modalWidth(MaxWidth::Small),

                Tables\Actions\ReplicateAction::make()
                    ->form(fn(Form $form) => static::form($form)->columns(2))
                    ->slideOver()
                    ->modalWidth(MaxWidth::Small),

                Tables\Actions\Action::make('closed')
                    ->label('Closed')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    // ->requiresConfirmation()
                    ->visible(fn($record) => $record->status !== 'closed')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'closed',
                        ]);

                        Notification::make()
                            ->title('Task berhasil diupdate')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Export Data')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->exporter(\App\Filament\Exports\TasksExporter::class),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            //'create' => Pages\CreateTask::route('/create'),
            // 'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
