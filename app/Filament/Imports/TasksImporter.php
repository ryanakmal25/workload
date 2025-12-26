<?php

namespace App\Filament\Imports;

use App\Models\Task;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

class TasksImporter extends Importer
{
    protected static ?string $model = Task::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('task_name')
                ->label('Item')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:100']),

            ImportColumn::make('note')
                ->label('Input')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('output')
                ->label('Output')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('staff_id')
                ->label('PIC (staff_id)')
                ->requiredMapping()
                ->rules(['required', 'integer', 'exists:staff,id']),

            ImportColumn::make('is_long_term')
                ->label('Long Term (0/1)')
                ->rules(['nullable', 'boolean']),

            ImportColumn::make('tanggal')
                ->label('Start Date')
                ->rules(['nullable', 'date']),

            ImportColumn::make('tanggal_akhir')
                ->label('End Date')
                ->rules(['nullable', 'date']),

            ImportColumn::make('estimasi_jam')
                ->label('Estimasi Jam')
                ->rules(['nullable', 'numeric']),

            ImportColumn::make('allocation_hours')
                ->label('Alokasi Jam')
                ->rules(['nullable', 'numeric']),

            ImportColumn::make('priority')
                ->label('Priority')
                ->rules(['required', 'string']),

            ImportColumn::make('status')
                ->label('Evaluasi Efektivitas')
                ->rules(['required', 'string']),
        ];
    }

    public function resolveRecord(): ?Task
    {
        return new Task();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Import tasks selesai dan data berhasil dimasukkan.';
    }
}
