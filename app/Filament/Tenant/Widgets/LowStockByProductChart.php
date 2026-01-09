<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Item;

class LowStockByProductChart extends ChartWidget
{
    protected static ?string $heading = 'Low Stock (Top 15)';

    protected function getData(): array
    {
        $tenantId = auth()->user()->tenant_id;

        $rows = Item::query()
            ->where('items.tenant_id', $tenantId) // ✅ FIX
            ->leftJoin(
                'stock_movements',
                'items.id',
                '=',
                'stock_movements.item_id'
            )
            ->selectRaw('
                items.name,
                COALESCE(SUM(
                    CASE
                        WHEN stock_movements.movement_type = "restock"
                            THEN stock_movements.quantity
                        WHEN stock_movements.movement_type = "sale"
                            THEN -stock_movements.quantity
                        ELSE 0
                    END
                ), 0) as stock
            ')
            ->groupBy('items.id', 'items.name')
            ->orderBy('stock', 'asc')
            ->limit(15)
            ->get();

        return [
            'datasets' => [[
                'label' => 'Stock Units',
                'data' => $rows->pluck('stock'),
            ]],
            'labels' => $rows->pluck('name'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
