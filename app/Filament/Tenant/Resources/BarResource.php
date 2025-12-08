<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\BarResource\Pages;
use App\Filament\Tenant\Resources\BarResource\RelationManagers;
use App\Models\Bar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Facades\Auth;


class BarResource extends Resource
{
    protected static ?string $model = Bar::class;

    protected static ?string $navigationIcon = 'heroicon-m-squares-plus';
    protected static ?string $navigationGroup = 'Configurations';
    protected static ?string $navigationLabel = 'Bars';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->isManager() || $user->isTenantAdmin() || $user->isAdmin() || $user->isManager();
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Forms\Components\Section::make('Bar Details')
                    ->schema([
                        Hidden::make('tenant_id')
                            ->default(fn() => Auth::user()->tenant_id),
                        Hidden::make('created_by')
                            ->default(fn() => Auth::user()->id),
                        Forms\Components\TextInput::make('name')
                            ->label('Bar Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(500)
                            ->nullable(),

                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                // BAR NAME
                Tables\Columns\TextColumn::make('name')
                    ->label('Bar Name')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-o-building-storefront')
                    ->toggleable(),

                // DESCRIPTION
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->placeholder('No description')
                    ->color('gray')
                    ->toggleable(),

                // CREATED BY
                Tables\Columns\BadgeColumn::make('creator.name')
                    ->label('Created By')
                    ->colors(['info'])
                    ->icons(['heroicon-o-user'])
                    ->sortable()
                    ->toggleable(),

                // CREATED DATE
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created On')
                    ->date('M d, Y')
                    ->sortable()
                    ->color('gray')
                    ->icon('heroicon-o-calendar')
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-trash'),
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
            'index' => Pages\ListBars::route('/'),
            'create' => Pages\CreateBar::route('/create'),
            'edit' => Pages\EditBar::route('/{record}/edit'),
        ];
    }
}
