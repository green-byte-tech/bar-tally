<?php

namespace App\Services\Item;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ItemTemplateService
{
    public function downloadTemplate(): BinaryFileResponse
    {
        $headers = [
            'name',
            'code',
            'brand',
            'category',
            'unit',
            'cost_price',
            'selling_price',
            'reorder_level',
        ];

        $csv = implode(',', $headers) . "\n";
        $csv .= "Sample Product,SAMPLE001,Brand,WINE,BTT,0,0,5\n";

        $fileName = 'item_import_template_' . now()->format('Ymd_His') . '.csv';
        $path = storage_path('app/' . $fileName);

        file_put_contents($path, $csv);

        return response()
            ->download($path)
            ->deleteFileAfterSend(true);
    }
}
