<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LowStockAlertWidget extends BaseWidget
{
    protected static ?string $heading = '🚨 Low Stock Alerts';

    protected static ?int $sort = 1;

  protected function getTableQuery(): Builder
{
    $tenantId = auth()->user()->tenant_id;

    return StockMovement::query()
        ->select([
            'stock_movements.item_id',
            DB::raw("
                SUM(
                    CASE
                        WHEN stock_movements.movement_type = 'restock' THEN stock_movements.quantity
                        WHEN stock_movements.movement_type = 'closing_stock' THEN stock_movements.quantity
                        WHEN stock_movements.movement_type = 'sale' THEN -stock_movements.quantity
                        ELSE 0
                    END
                ) AS current_stock
            "),
        ])
        ->join('items', 'items.id', '=', 'stock_movements.item_id')
        ->where('stock_movements.tenant_id', $tenantId)
        ->groupBy(
            'stock_movements.item_id',
            'items.reorder_level',
            'items.name',
            'items.cost_price',
            'items.selling_price'
        )
        ->havingRaw('current_stock > 0')
        ->havingRaw('current_stock <= items.reorder_level')
        ->orderByRaw('current_stock ASC') // ✅ VALID ORDER
        ->with('item');
}

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('item.name')
                ->label('Product')
                ->weight('bold')
                ->color('danger'),

            Tables\Columns\TextColumn::make('current_stock')
                ->label('Current Stock')
                ->badge()
                ->color('danger'),

            Tables\Columns\TextColumn::make('item.reorder_level')
                ->label('Reorder Level')
                ->badge()
                ->color('warning'),

            Tables\Columns\TextColumn::make('item.cost_price')
                ->label('Cost')
                ->money('kes', true),

            Tables\Columns\TextColumn::make('item.selling_price')
                ->label('Selling Price')
                ->money('kes', true),
        ];
    }

    protected function getTableRowClasses(): ?\Closure
    {
        return fn () => 'bg-red-50 dark:bg-red-900/40';
    }
}
