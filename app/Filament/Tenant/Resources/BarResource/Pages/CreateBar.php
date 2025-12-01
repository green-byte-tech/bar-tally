<?php

namespace App\Filament\Tenant\Resources\BarResource\Pages;

use App\Filament\Tenant\Resources\BarResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBar extends CreateRecord
{
    protected static string $resource = BarResource::class;
}
