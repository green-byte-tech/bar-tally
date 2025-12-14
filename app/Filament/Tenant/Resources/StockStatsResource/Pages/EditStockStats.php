<?php

namespace App\Filament\Tenant\Resources\StockStatsResource\Pages;

use App\Filament\Tenant\Resources\StockStatsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockStats extends EditRecord
{
    protected static string $resource = StockStatsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
