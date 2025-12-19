<?php

namespace App\Services\Item;

use App\Models\Item;
use App\Support\SalesImportHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ItemImportService
{
    public function preparePreview(string $uploadedPath): array
    {
        $ext = pathinfo($uploadedPath, PATHINFO_EXTENSION);
        $permanent = 'imports/items/' . Str::uuid() . '.' . $ext;

        Storage::disk('local')->copy($uploadedPath, $permanent);

        $rows = SalesImportHandler::loadRows(
            Storage::disk('local')->path($permanent)
        );

        return [
            'rows' => $rows,
            'file' => $permanent,
        ];
    }

    public function commit(array $rows, int $tenantId, int $userId): void
    {
        DB::transaction(function () use ($rows, $tenantId, $userId) {

            foreach ($rows as $row) {

                $row = collect($row)
                    ->mapWithKeys(fn($v, $k) => [strtolower(trim($k)) => $v])
                    ->toArray();

                $code = trim($row['code'] ?? '');
                $name = trim($row['name'] ?? '');

                if (!$code || !$name) {
                    continue;
                }

                Item::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'code'      => $code,
                    ],
                    [
                        'name'          => $name,
                        'brand'         => null,
                        'category'      => null,
                        'unit'          => $row['unit'] ?? 'PCS',
                        'cost_price'    => (float) ($row['cost_price'] ?? 0),
                        'selling_price' => (float) ($row['selling_price'] ?? 0),
                        'reorder_level' => (int) ($row['reorder_level'] ?? 0),
                        'is_active'     => 1,
                        'created_by'    => $userId,
                        'updated_by'    => $userId,
                    ]
                );
            }
        });
    }
}
