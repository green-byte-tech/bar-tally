<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\CounterInventoryResource\Pages;
use App\Filament\Tenant\Resources\CounterInventoryResource\RelationManagers;
use App\Models\CounterInventory;
use App\Models\StockMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\Counter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;


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

                // Default: force filter until user chooses a counter
                $counterId = request('tableFilters')['counter_id']['value'] ?? null;

                $base = StockMovement::select([
                    'item_id as id',
                    'item_id',
                    'counter_id',
                    DB::raw("SUM(CASE WHEN movement_type = 'transfer_to_counter' THEN quantity ELSE 0 END) AS stock_in"),
                    DB::raw("SUM(CASE WHEN movement_type = 'sale' THEN quantity ELSE 0 END) AS stock_out"),
                    DB::raw("(SUM(CASE WHEN movement_type = 'transfer_to_counter' THEN quantity ELSE 0 END)
                           - SUM(CASE WHEN movement_type = 'sale' THEN quantity ELSE 0 END)) AS current_stock"),
                ])
                ->join('items', 'items.id', '=', 'stock_movements.item_id')
                ->where('stock_movements.tenant_id', Auth::user()->tenant_id);

                if ($counterId) {
                    $base->where('stock_movements.counter_id', $counterId);
                } else {
                    $base->where('stock_movements.counter_id', -1); // show empty until chosen
                }

                return $base
                    ->groupBy('item_id', 'counter_id', 'items.reorder_level')
                    ->orderBy('item_id', 'asc');
            })

            ->columns([
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Item')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('stock_in')
                    ->label('Stock In')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_out')
                    ->label('Sold')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('current_stock')
                    ->label('Current Stock')
                    ->formatStateUsing(function ($state, $record) {
                        $level = $record->item->reorder_level;

                        if ($state == 0) {
                            return "0 (Out of Stock)";
                        }

                        if ($state < $level) {
                            return "{$state} (Below Reorder)";
                        }

                        if ($state == $level) {
                            return "{$state} (At Reorder Level)";
                        }

                        return "{$state} (OK)";
                    })
                    ->colors([
                        'danger' => fn ($record) => $record->current_stock == 0,
                        'warning' => fn ($record) => $record->current_stock > 0 && $record->current_stock < $record->item->reorder_level,
                        'warning' => fn ($record) => $record->current_stock == $record->item->reorder_level,
                        'success' => fn ($record) => $record->current_stock > $record->item->reorder_level,
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('item.reorder_level')
                    ->label('Reorder Level')
                    ->sortable(),
            ])

            ->filters([
                SelectFilter::make('counter_id')
                    ->label('Counter')
                    ->options(
                        Counter::where('tenant_id', Auth::user()->tenant_id)->pluck('name', 'id')
                    )
                    ->placeholder('Select Counter'),
            ])

            ->actions([])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCounterInventories::route('/'),
        ];
    }
}
