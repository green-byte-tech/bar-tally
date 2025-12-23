<?php

namespace App\Support;

use Maatwebsite\Excel\Facades\Excel;

class PhysicalCountImportHandler
{
    public static function loadRows(string $filePath): array
    {
        // Load raw rows (same as Stock import)
        $data = Excel::toArray([], $filePath)[0];

        if (count($data) < 2) {
            return [];
        }

        // Normalize header
        $header = array_map(
            fn ($h) => strtolower(trim($h)),
            $data[0]
        );

        unset($data[0]);

        $rows = [];

        foreach ($data as $row) {
            $combined = array_combine($header, $row);

            $product = trim($combined['product'] ?? '');
            $sku     = trim($combined['sku'] ?? '');

            if (! $product) {
                continue;
            }

            // Every column except product + sku is a counter
            foreach ($combined as $key => $value) {

                if (in_array($key, ['product', 'sku'])) {
                    continue;
                }

                if ($value === null || $value === '') {
                    continue;
                }

                $rows[] = [
                    'product'  => $product,
                    'sku'      => $sku,
                    'counter'  => trim($key),
                    'quantity' => (int) $value,
                ];
            }
        }

        return $rows;
    }
}
