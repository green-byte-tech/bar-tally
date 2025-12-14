<?php

namespace App\Filament\Tenant\Resources\StockStatsResource\Pages;

use App\Filament\Tenant\Resources\StockStatsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockStats extends ListRecords
{
    protected static string $resource = StockStatsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
