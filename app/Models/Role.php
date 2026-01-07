<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Models\Staff;
use App\Models\Task;

class Role extends Model
{
    use HasFactory;

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function tasks(): HasManyThrough
    {
        return $this->hasManyThrough(Task::class, Staff::class);
    }

    protected $fillable = [
        'name',
        'description',
    ];
}
