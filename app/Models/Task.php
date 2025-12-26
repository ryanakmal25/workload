<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use app\Models\Staff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    protected $fillable = [
        'task_name',
        'staff_id',
        'tanggal',
        'tanggal_akhir',
        'estimasi_jam',
        'status',
        'input',
        'is_long_term',
        'allocation_hours',
        'priority',
        'output',
        'total_overdue',
    ];
    protected static function booted()
    {
        static::saving(function ($task) {
            $today = \Carbon\Carbon::today();

            if (in_array($task->status, ['closed', 'postponed'])) {
                $task->total_overdue = 0;
                return;
            }

            if (!$task->is_long_term && $task->tanggal) {
                $target = \Carbon\Carbon::parse($task->tanggal);
                $task->total_overdue = $today->greaterThan($target)
                    ? $today->diffInDays($target)
                    : 0;
            } elseif ($task->is_long_term && $task->tanggal_akhir) {
                $end = \Carbon\Carbon::parse($task->tanggal_akhir);
                $task->total_overdue = $today->greaterThan($end)
                    ? $today->diffInDays($end)
                    : 0;
            } else {
                $task->total_overdue = 0;
            }
        });
    }
}
