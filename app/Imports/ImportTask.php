<?php

// namespace App\Imports;

// use App\Models\Task;
// use EightyNine\ExcelImport\Facades\ExcelImportAction;

// class ImportTask extends ExcelImportAction
// {

//     public static function model(): string
//     {
//         return Task::class;
//     }


//     public static function columns(): array
//     {
//         return [
//             'task_name'    => 'task_name',
//             'note'         => 'note',
//             'output'       => 'output',
//             'tanggal'      => 'tanggal',
//             'estimasi_jam' => 'estimasi_jam',
//             'staff_id'     => 'staff_id',
//             'status'       => 'status',
//         ];
//     }


//     public static function rules(): array
//     {
//         return [
//             // 'task_name'    => ['required', 'string', 'max:255'],
//             // 'note'         => ['nullable', 'string'],
//             // 'output'       => ['nullable', 'string'],
//             // 'tanggal'      => ['required', 'date'],
//             // 'estimasi_jam' => ['required', 'integer'],
//             // 'staff_id'     => ['required', 'exists:staff,id'],
//             // 'status'       => ['required', 'in:todo,progress,done,overdue,postponed'],
//         ];
//     }

//     public static function mutateData(array $row): array
//     {
//         // contoh: ubah format tanggal
//         if (!empty($row['tanggal'])) {
//             $row['tanggal'] = date('Y-m-d', strtotime($row['tanggal']));
//         }

//         return $row;
//     }
//     public static function afterImport(array $records): void
//     {
//         \Filament\Notifications\Notification::make()->title('Import berhasil')->body(count($records) . ' tasks berhasil diimport.')->success()->send();
//     }
// } -->
