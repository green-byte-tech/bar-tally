<?php

namespace App\Filament\Tenant\Resources\CounterResource\Pages;

use App\Filament\Tenant\Resources\CounterResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCounter extends CreateRecord
{
    protected static string $resource = CounterResource::class;
}
