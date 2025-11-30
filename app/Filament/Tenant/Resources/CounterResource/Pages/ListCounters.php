<?php

namespace App\Filament\Tenant\Resources\CounterResource\Pages;

use App\Filament\Tenant\Resources\CounterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCounters extends ListRecords
{
    protected static string $resource = CounterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Add New Counter')->slideOver(),
        ];
    }
}
