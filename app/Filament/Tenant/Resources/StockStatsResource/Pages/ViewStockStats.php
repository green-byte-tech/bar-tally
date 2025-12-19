<?php

namespace App\Filament\Tenant\Resources\StockStatsResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Tenant\Resources\StockStatsResource;

class ViewStockStats extends ListRecords
{
    protected static string $resource = StockStatsResource::class;

    protected function getHeaderWidgets(): array
    {
        return StockStatsResource::getWidgets();
    }


}
