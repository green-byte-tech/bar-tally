<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\Inventory\InventoryAnalyticsService;

class StockOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $analytics = new InventoryAnalyticsService($tenantId);

        $salesValue = $analytics->salesValue();
        $profit     = $analytics->grossProfit();
        $variance   = $analytics->varianceValue(today());

        $stats = [

            Stat::make('Total Products', $analytics->totalProducts())
                ->description('Active products 📦')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Stock In Value', 'KES ' . number_format($analytics->stockInValue(), 0))
                ->description('Inventory received (at cost)')
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('info'),

            Stat::make('Total Sales Value', 'KES ' . number_format($salesValue, 0))
                ->description('Revenue generated 💰')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Gross Profit', 'KES ' . number_format($profit, 0))
                ->description('Sales minus cost')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($profit >= 0 ? 'success' : 'danger'),

            Stat::make('Current Stock Value', 'KES ' . number_format($analytics->currentStockValue(), 0))
                ->description('Unsold inventory (at cost)')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color($analytics->currentStockValue() > 0 ? 'success' : 'danger'),

            Stat::make('Variance Value Today', 'KES ' . number_format($variance, 2))
                ->description('Must be zero')
                ->descriptionIcon(
                    $variance == 0
                        ? 'heroicon-m-check-circle'
                        : 'heroicon-m-exclamation-triangle'
                )
                ->color($variance == 0 ? 'success' : 'danger'),


        ];
        foreach ($analytics->salesValueByCounter() as $counter) {
            $stats[] = Stat::make(
                "Counter: {$counter['counter']} Sales",
                'KES ' . number_format($counter['value'], 0)
            )
                ->description('Sold value 💰')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('success');
        }

        foreach ($analytics->stockValueByCounter() as $counter) {
            $stats[] = Stat::make(
                "Counter: {$counter['counter']} Stock",
                'KES ' . number_format($counter['value'], 0)
            )
                ->description('Remaining stock value 📦')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color($counter['value'] > 0 ? 'info' : 'danger');
        }


        return $stats;
    }
}
