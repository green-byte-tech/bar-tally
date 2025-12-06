<?php

namespace App\Filament\Tenant\Resources\CounterInventoryResource\Pages;

use App\Filament\Tenant\Resources\CounterInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCounterInventory extends EditRecord
{
    protected static string $resource = CounterInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
