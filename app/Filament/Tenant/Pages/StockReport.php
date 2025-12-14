<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;

class StockReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Reports & Analytics';
    protected static ?string $pluralModelLabel = 'Stock Analytics Dashboard';
    protected static ?string $navigationLabel = 'Variance Report';

    protected static string $view = 'filament.tenant.pages.stock-report';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->isManager() || $user->isTenantAdmin() || $user->isAdmin();
    }
}
