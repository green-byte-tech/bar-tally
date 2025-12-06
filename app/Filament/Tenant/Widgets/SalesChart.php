<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\StockMovement;

class SalesChart extends ChartWidget
{

    protected static ?string $heading = 'Sales (Last 7 Days)';

    protected function getData(): array
    {
        // Define movement type for sales
        $SALE_TYPE = 'sale';

        $dates = collect(range(6, 0))
            ->map(fn($i) => now()->subDays($i)->format('Y-m-d'));

        // Count sales per day (you may sum quantity if you track qty)
        $salesCounts = $dates->map(function ($date) use ($SALE_TYPE) {
            return StockMovement::whereDate('movement_date', $date)
                ->where('movement_type', $SALE_TYPE)
                ->count();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Sales Count',
                    'data' => $salesCounts,
                ],
            ],
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
