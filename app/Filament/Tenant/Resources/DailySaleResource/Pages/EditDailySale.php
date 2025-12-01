<?php

namespace App\Filament\Tenant\Resources\DailySaleResource\Pages;

use App\Filament\Tenant\Resources\DailySaleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailySale extends EditRecord
{
    protected static string $resource = DailySaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
