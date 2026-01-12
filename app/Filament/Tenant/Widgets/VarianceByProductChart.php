<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\StockMovement;
use App\Models\Item;
use Illuminate\Support\Collection;

class VarianceByProductChart extends ChartWidget
{
    protected static ?string $heading = 'Stock Variance by Product';
    protected static ?int $sort = 40;

    protected function getData(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $date = request()->get('date', today());

        $variances = StockMovement::with('item')
            ->where('tenant_id', $tenantId)
            ->whereDate('movement_date', $date)
            ->get()
            ->groupBy('item_id')
            ->map(function (Collection $movements) {
                $opening = $movements->where('movement_type', 'opening_stock')->sum('quantity');
                $restock = $movements->where('movement_type', 'restock')->sum('quantity');
                $sold    = $movements->where('movement_type', 'sale')->sum('quantity');
                $closing = $movements->where('movement_type', 'closing_stock')->sum('quantity');

                $expected = $opening + $restock - $sold;
                $variance = $expected - $closing;

                return [
                    'label' => $movements->first()->item->name ?? 'Unknown',
                    'value' => $variance,
                ];
            })
            // 🔴 ONLY show products with variance
            ->filter(fn($v) => $v['value'] !== 0)
            ->values();

        return [
            'datasets' => [
                [
                    'label' => 'Variance Units',
                    'data' => $variances->pluck('value')->toArray(),
                    'backgroundColor' => '#ef4444', // red
                ],
            ],
            'labels' => $variances->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
