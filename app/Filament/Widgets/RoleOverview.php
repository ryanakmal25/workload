<?php

namespace App\Filament\Widgets;

use App\Models\Role;
use Filament\Widgets\Widget;

class RoleOverview extends Widget
{
    protected static string $view = 'filament.widgets.role-overview';
    protected int|string|array $columnSpan = 12;
    protected static ?int $sort = 5;

    public function getViewData(): array
    {
        // role aktif dari query string (?role=...)
        $activeRoleId = request()->query('role', Role::first()?->id);

        // ambil semua role + jumlah task
        $roles = Role::withCount('tasks')->get();

        return compact('roles', 'activeRoleId');
    }

}
