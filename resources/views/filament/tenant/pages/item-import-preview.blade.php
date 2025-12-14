<x-filament-panels::page>

    <h2 class="text-xl font-bold mb-4">
        Preview Item Import
    </h2>

    @php
        $firstRow = $rows[array_key_first($rows)] ?? [];
    @endphp

    <div class="overflow-x-auto rounded-lg shadow bg-white dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-gray-100 dark:bg-gray-800">
                <tr>
                    @foreach(array_keys($firstRow) as $col)
                        <th class="px-4 py-2 text-left text-sm font-medium">
                            {{ ucfirst(str_replace('_', ' ', $col)) }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($rows as $row)
                    <tr>
                        @foreach($row as $value)
                            <td class="px-4 py-2 text-sm">
                                <span class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded">
                                    {{ $value ?: '-' }}
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
        color="success">
        Confirm Import
    </x-filament::button>

</x-filament-panels::page>
