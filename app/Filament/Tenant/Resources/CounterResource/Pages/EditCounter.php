<?php

namespace App\Filament\Tenant\Resources\CounterResource\Pages;

use App\Filament\Tenant\Resources\CounterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCounter extends EditRecord
{
    protected static string $resource = CounterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
