<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\CounterInventoryResource\Pages;
use App\Models\StockMovement;
use App\Models\Item;
use App\Models\Counter;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\SelectFilter;
use Filament\Resources\Resource;


class CounterInventoryResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Cashier';
    protected static ?string $navigationLabel = 'Counter Inventory';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $counterId = Auth::user()->counters()->first()?->id;

                if (!$counterId) {
                    return Item::query()->whereRaw('1 = 0'); // force empty until counter selected
                }

                return Item::query()
                    ->select([
                        'items.id',
                        'items.name AS item_name',
                        'items.reorder_level',
                        DB::raw("
                COALESCE(SUM(CASE WHEN sm.movement_type = 'transfer_to_counter' THEN sm.quantity END), 0)
                AS stock_in
            "),
                        DB::raw("
                COALESCE(SUM(CASE WHEN sm.movement_type = 'sale' THEN sm.quantity END), 0)
                AS stock_out
            "),
                        DB::raw("
                COALESCE(SUM(CASE WHEN sm.movement_type = 'transfer_to_counter' THEN sm.quantity END), 0)
                -
                COALESCE(SUM(CASE WHEN sm.movement_type = 'sale' THEN sm.quantity END), 0)
                AS current_stock
            "),
                    ])
                    ->leftJoin('stock_movements as sm', function ($join) use ($counterId) {
                        $join->on('items.id', '=', 'sm.item_id')
                            ->where('sm.counter_id', $counterId)
                            ->where('sm.tenant_id', Auth::user()->tenant_id);
                    })
                    ->where('items.tenant_id', Auth::user()->tenant_id)
                    ->groupBy('items.id', 'items.name', 'items.reorder_level')
                    ->orderBy('items.name');
            })


            ->columns([
                Tables\Columns\TextColumn::make('item_name')
                    ->label('Item')
                    ->weight('bold')
                    ->icon('heroicon-o-cube')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('stock_in')
                    ->label('Stock In')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('stock_out')
                    ->label('Sold')
                    ->sortable()
                    ->badge()
                    ->color('danger'),

                Tables\Columns\BadgeColumn::make('current_stock')
                    ->label('Current Stock')
                    ->colors([
                        'danger' => fn($record) => $record->current_stock <= 0,
                        'warning' => fn($record) => $record->current_stock > 0 && $record->current_stock <= $record->reorder_level,
                        'success' => fn($record) => $record->current_stock > $record->reorder_level,
                    ])
                    ->formatStateUsing(function ($state, $record) {

                        if ($state <= 0) {
                            return "{$state} (Out of Stock)";
                        }

                        if ($state < $record->reorder_level) {
                            return "{$state} (Below Reorder)";
                        }

                        if ($state == $record->reorder_level) {
                            return "{$state} (At Reorder)";
                        }

                        return "{$state} (OK)";
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('reorder_level')
                    ->label('Reorder Level')
                    ->color('gray')
                    ->sortable()
                    ->icon('heroicon-o-arrow-trending-down'),
            ])

            ->filters([
                SelectFilter::make('counter_id')
                    ->label('Counter')
                    ->options(
                        Counter::where('tenant_id', Auth::user()->tenant_id)->pluck('name', 'id')
                    )
                    ->placeholder('Select Counter')
                    ->searchable(),
            ])

            ->defaultSort('item_name')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCounterInventories::route('/'),
        ];
    }
}
