<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use App\Services\Item\ItemImportService;

class ItemImportPreview extends Page
{
    protected static string $view = 'filament.tenant.pages.item-import-preview';

    public array $rows = [];

    public function mount()
    {
        $this->rows = session('item-import-rows', []);

        if (empty($this->rows)) {
            return redirect()->route('filament.tenant.resources.items.index');
        }
    }

    public function import()
    {
        $service = app(ItemImportService::class);

        $service->commit(
            $this->rows,
            auth()->user()->tenant_id,
            auth()->id()
        );

        session()->forget([
            'item-import-rows',
            'item-import-file',
        ]);

        session()->flash('success', 'Items imported successfully.');

        return redirect()->route('filament.tenant.resources.items.index');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
