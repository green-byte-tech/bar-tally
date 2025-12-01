<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\ControllerResource\Pages;
use App\Filament\Tenant\Resources\ControllerResource\RelationManagers;
use App\Models\Controller;
use App\Models\StockMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ControllerResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-s-lock-closed';

    protected static ?string $navigationGroup = 'Controller';

    protected static ?string $navigationLabel = 'Closing Count';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListControllers::route('/'),
            'create' => Pages\CreateController::route('/create'),
            'edit' => Pages\EditController::route('/{record}/edit'),
        ];
    }
}
