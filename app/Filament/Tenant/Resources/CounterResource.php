<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\CounterResource\Pages;
use App\Filament\Tenant\Resources\CounterResource\RelationManagers;
use App\Models\Counter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;

class CounterResource extends Resource
{
    protected static ?string $model = Counter::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';
    protected static ?string $navigationGroup = 'Configurations';
    protected static ?string $navigationLabel = 'Counters';

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
                Forms\Components\Section::make('Counter Details')
                    ->schema([
                        Hidden::make('tenant_id')
                            ->default(fn() => Auth::user()->tenant_id),
                        Hidden::make('created_by')
                            ->default(fn() => Auth::user()->id),
                        Forms\Components\Select::make('bar_id')
                            ->label('Bar')
                            ->required()
                            ->options(
                                \App\Models\Bar::query()
                                    ->where('tenant_id', Auth::user()->tenant_id)
                                    ->pluck('name', 'id')
                            )
                            ->searchable(),
                        Forms\Components\TextInput::make('name')
                            ->label('Counter Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(500)
                            ->nullable(),
                        Forms\Components\Select::make('assigned_user')
                            ->label('Assigned User')
                            ->required()
                            ->searchable()
                            ->options(
                                \App\Models\User::where('tenant_id', Auth::user()->tenant_id)
                                    ->pluck('name', 'id')
                            ),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
       return $table
    ->columns([

        // BAR
        Tables\Columns\BadgeColumn::make('bar.name')
            ->label('Bar')
            ->colors(['primary'])
            ->icons(['heroicon-o-building-storefront'])
            ->sortable()
            ->toggleable(),

        // COUNTER NAME
        Tables\Columns\TextColumn::make('name')
            ->label('Counter')
            ->sortable()
            ->searchable()
            ->weight('bold')
            ->icon('heroicon-o-rectangle-group')
            ->toggleable(),

        // DESCRIPTION
        Tables\Columns\TextColumn::make('description')
            ->label('Description')
            ->limit(40)
            ->placeholder('No description')
            ->color('gray')
            ->toggleable(),

        // CREATED BY
        Tables\Columns\TextColumn::make('creator.name')
            ->label('Created By')
            ->sortable()
            ->badge()
            ->color('info')
            ->icon('heroicon-o-user')
            ->toggleable(),

        // ASSIGNED USER
        Tables\Columns\BadgeColumn::make('assignedUser.name')
            ->label('Assigned User')
            ->colors(['success'])
            ->icons(['heroicon-o-user-circle'])
            ->sortable()
            ->searchable()
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
        // add later
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
            'index' => Pages\ListCounters::route('/'),
            'create' => Pages\CreateCounter::route('/create'),
            'edit' => Pages\EditCounter::route('/{record}/edit'),
        ];
    }
}
