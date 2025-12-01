<?php

namespace App\Filament\Tenant\Resources\StocksResource\Pages;

use App\Filament\Tenant\Resources\StocksResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStocks extends EditRecord
{
    protected static string $resource = StocksResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
