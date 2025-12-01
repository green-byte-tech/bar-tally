<?php

namespace App\Filament\Tenant\Resources\DailySaleResource\Pages;

use App\Filament\Tenant\Resources\DailySaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailySales extends ListRecords
{
    protected static string $resource = DailySaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
