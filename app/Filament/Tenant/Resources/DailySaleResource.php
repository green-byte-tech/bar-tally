<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\DailySaleResource\Pages;
use App\Filament\Tenant\Resources\DailySaleResource\RelationManagers;
use App\Models\DailySale;
use App\Models\StockMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Item;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Facades\Auth;

class DailySaleResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-c-document-currency-dollar';

    protected static ?string $navigationGroup = 'Cashier';
    protected static ?string $navigationLabel = 'Record Sales';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //

                Forms\Components\Section::make('Receive Stock')
                    ->description('Record stock received into central store')
                    ->schema([

                        Forms\Components\Select::make('item_id')
                            ->label('Item')
                            ->options(
                                Item::query()
                                    ->where('tenant_id', Auth::user()->tenant_id)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity Received')
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        Forms\Components\Hidden::make('movement_type')
                            ->default(fn() => 'restock'),

                        Forms\Components\DatePicker::make('movement_date')
                            ->label('Date')
                            ->default(now())
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->nullable(),

                        Forms\Components\Hidden::make('tenant_id')
                            ->default(fn() => Auth::user()->tenant_id),

                        Forms\Components\Hidden::make('created_by')
                            ->default(fn() => Auth::id()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table

            // Only show stock intake rows
            ->modifyQueryUsing(
                fn($query) =>
                $query->where('movement_type', 'restock')
            )

            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('TimeStamp')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Product')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('quantity')
                    ->label('Qty')
                    ->colors([
                        'success' => fn($state) => $state > 0,
                    ])
                    ->formatStateUsing(fn($state) => "+{$state}"),

                Tables\Columns\TextColumn::make('notes')
                    ->limit(30)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Recorded By')
                    ->sortable(),
            ])

            ->defaultSort('movement_date', 'desc')

            ->actions([])

            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
            'index' => Pages\ListDailySales::route('/'),
            'create' => Pages\CreateDailySale::route('/create'),
            'edit' => Pages\EditDailySale::route('/{record}/edit'),
        ];
    }
}
