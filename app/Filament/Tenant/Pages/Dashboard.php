<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{

    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->isManager() || $user->isTenantAdmin() || $user->isAdmin();
    }
    public function getWidgets(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'Dashboard';
    }
}
