<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\StockMovement;

class StockByProductChart extends ChartWidget
{
    protected static ?string $heading = 'Current Stock by Product';
    protected static ?int $sort = 3;

    protected function getFilters(): array
    {
        return [
            'all' => 'All Time',
            'month' => 'This Month',
        ];
    }

    protected function getData(): array
    {
        [$from, $to] = $this->filter === 'month'
            ? [now()->startOfMonth(), now()->endOfMonth()]
            : [null, null];

        $query = StockMovement::query()
            ->join('items', 'items.id', '=', 'stock_movements.item_id')
            ->selectRaw('
                items.name as product,
                SUM(
                    CASE
                        WHEN movement_type = "restock" THEN quantity
                        WHEN movement_type = "sale" THEN -quantity
                        ELSE 0
                    END
                ) as stock
            ')
            ->groupBy('items.id', 'items.name')
            ->orderByDesc('stock');

        if ($from && $to) {
            $query->whereBetween('movement_date', [$from, $to]);
        }

        $rows = $query->get();

        return [
            'datasets' => [
                [
                    'label' => 'Stock Units',
                    'data' => $rows->pluck('stock'),
                ],
            ],
            'labels' => $rows->pluck('product'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
