<x-filament-panels::page>

    <h2 class="text-xl font-bold mb-4">
        Preview Physical Count Import
    </h2>

    @php
        /**
         * Pivot rows for display ONLY
         * rows = product+sku+counter+quantity
         */

        $grouped = collect($rows)->groupBy(fn ($r) =>
            ($r['product'] ?? '') . '|' . ($r['sku'] ?? '')
        );

        // Collect unique counters
        $counters = collect($rows)
            ->pluck('counter')
            ->unique()
            ->values();
    @endphp

    <div class="overflow-hidden rounded-lg shadow bg-white dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">

            {{-- Header --}}
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-2 text-left">Product</th>
                    <th class="px-4 py-2 text-left">SKU</th>

                    @foreach($counters as $counter)
                        <th class="px-4 py-2 text-center">
                            {{ ucfirst($counter) }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            {{-- Body --}}
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($grouped as $group)
                    @php
                        $first = $group->first();
                        $byCounter = $group->keyBy('counter');
                    @endphp

                    <tr>
                        <td class="px-4 py-2 font-semibold">
                            {{ $first['product'] }}
                        </td>

                        <td class="px-4 py-2 text-gray-500">
                            {{ $first['sku'] ?? '-' }}
                        </td>

                        @foreach($counters as $counter)
                            @php
                                $qty = $byCounter[$counter]['quantity'] ?? 0;
                            @endphp

                            <td class="px-4 py-2 text-center">
                                <span class="px-2 py-1 rounded
                                    {{ $qty > 0
                                        ? 'bg-green-200 dark:bg-green-700'
                                        : 'bg-gray-200 dark:bg-gray-700' }}">
                                    {{ $qty }}
                                </span>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>

        </table>
    </div>

    <x-filament::button
        class="mt-4"
        wire:click="import"
        wire:loading.attr="disabled"
        color="success">
        Confirm Import
    </x-filament::button>

</x-filament-panels::page>
