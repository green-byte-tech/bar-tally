<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Item;
use Illuminate\Support\Facades\DB;

class StockByProductChart extends ChartWidget
{
    protected static ?string $heading = 'Current Stock by Product';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $tenantId = auth()->user()->tenant_id;

        $rows = Item::query()
            ->where('items.tenant_id', $tenantId)
            ->leftJoin('stock_movements', function ($join) {
                $join->on('items.id', '=', 'stock_movements.item_id');
            })
            ->selectRaw('
                items.name as product,
                COALESCE(
                    SUM(
                        CASE
                            WHEN stock_movements.movement_type = "restock"
                                THEN stock_movements.quantity
                            WHEN stock_movements.movement_type = "sale"
                                THEN -stock_movements.quantity
                            ELSE 0
                        END
                    ), 0
                ) as stock
            ')
            ->groupBy('items.id', 'items.name')
            ->orderBy('items.name')
            ->get();

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
