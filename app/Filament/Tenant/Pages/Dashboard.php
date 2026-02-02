<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Panel;
use App\Filament\Tenant\Widgets\StockValueOverviewWidget;
use App\Filament\Tenant\Widgets\LowStockAlertWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;


class Dashboard extends BaseDashboard
{

    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $navigationGroup = 'Dashboard';
    protected static ?int $navigationSort = 1;
    protected static bool $shouldRegisterNavigation = false; // This hides it from the menu

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->sidebarFullyCollapsibleOnDesktop();
    }
    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->isManager() || $user->isTenantAdmin() || $user->isAdmin();
    }
    public function getWidgets(): array
    {
        return [
            StockValueOverviewWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Dashboard';
    }
}
