<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockChart extends ChartWidget
{
    protected static ?string $heading = 'Current Stock Levels';

    protected function getData(): array
    {
        // Define movement types
        $incoming = ['in', 'purchase', 'adjust_plus'];
        $outgoing = ['sale', 'adjust_minus', 'counter_transfer'];

        // Aggregate stock balances
        $stocks = StockMovement::select(
            'item_id',
            DB::raw("SUM(CASE WHEN movement_type IN ('in','purchase','adjust_plus') THEN 1 ELSE 0 END) -
                         SUM(CASE WHEN movement_type IN ('sale','adjust_minus','counter_transfer') THEN 1 ELSE 0 END)
                         AS balance")
        )
            ->groupBy('item_id')
            ->with('item')
            ->orderBy('balance', 'desc')
            ->take(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Stock Balance',
                    'data' => $stocks->pluck('balance'),
                ],
            ],
            'labels' => $stocks->pluck('item.name'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
