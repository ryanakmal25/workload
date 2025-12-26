<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use app\Models\Staff;

class role extends Model
{
    use HasFactory;

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    protected $fillable =
    [
        'name',
        'description'

    ];
}
