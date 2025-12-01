<?php

namespace App\Filament\Tenant\Resources\ControllerResource\Pages;

use App\Filament\Tenant\Resources\ControllerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditController extends EditRecord
{
    protected static string $resource = ControllerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
