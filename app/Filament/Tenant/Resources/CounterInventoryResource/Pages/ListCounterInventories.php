<?php

namespace App\Filament\Tenant\Resources\CounterInventoryResource\Pages;

use App\Filament\Tenant\Resources\CounterInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Tenant\Resources\CounterInventoryResource\Widgets\InventoryChart;

class ListCounterInventories extends ListRecords
{
    protected static string $resource = CounterInventoryResource::class;
    protected static ?string $title = 'Counter Inventory Records';
    protected function getHeaderWidgets(): array
    {
        return [

        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
