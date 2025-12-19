<?php

namespace App\Filament\Tenant\Resources\StocksResource\Pages;

use App\Filament\Tenant\Resources\StocksResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\StockMovement;
use App\Models\Item;
use Filament\Notifications\Notification;

class CreateStocks extends CreateRecord
{
    protected static string $resource = StocksResource::class;

    protected static ?string $title = 'Restock Product';

    protected function handleRecordCreation(array $data): StockMovement
    {
        $sum = array_sum($data['counters']);

        if ($sum !== (int) $data['total_quantity']) {
            Notification::make()
                ->title('Quantity mismatch')
                ->body("Counter total ($sum) must equal Total Quantity ({$data['total_quantity']})")
                ->danger()
                ->send();

            // throw \Filament\Support\Exceptions\Halt::make();
        }

        foreach ($data['counters'] as $counterId => $qty) {
            if ($qty > 0) {
                StockMovement::create([
                    'tenant_id'     => $data['tenant_id'],
                    'item_id'       => $data['item_id'],
                    'counter_id'    => $counterId,
                    'quantity'      => $qty,
                    'movement_type' => $data['movement_type'],
                    'movement_date' => $data['movement_date'],
                    'notes'         => $data['notes'],
                    'created_by'    => $data['created_by'],
                ]);
            }
        }

        return StockMovement::latest()->first();
    }
}
