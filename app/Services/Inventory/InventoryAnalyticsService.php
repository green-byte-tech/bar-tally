<?php

namespace App\Services\Inventory;

use App\Models\StockMovement;
use App\Models\Item;
use Illuminate\Support\Collection;

class InventoryAnalyticsService
{
    public function __construct(
        protected int $tenantId
    ) {}

    /* =========================
     | BASIC COUNTS
     * ========================= */

    public function totalProducts(): int
    {
        return Item::where('tenant_id', $this->tenantId)->count();
    }

    /* =========================
     | SALES
     * ========================= */

    public function sales(): Collection
    {
        return StockMovement::with('item')
            ->where('tenant_id', $this->tenantId)
            ->where('movement_type', 'sale')
            ->get();
    }

    public function salesQuantity(): int
    {
        return $this->sales()->sum('quantity');
    }

    public function salesValue(): float
    {
        return $this->sales()->sum(
            fn ($m) => $m->quantity * ($m->item->selling_price ?? 0)
        );
    }

    public function costOfSales(): float
    {
        return $this->sales()->sum(
            fn ($m) => $m->quantity * ($m->item->cost_price ?? 0)
        );
    }

    public function grossProfit(): float
    {
        return $this->salesValue() - $this->costOfSales();
    }

    /* =========================
     | STOCK IN & CURRENT STOCK
     * ========================= */

    public function stockInValue(): float
    {
        return StockMovement::with('item')
            ->where('tenant_id', $this->tenantId)
            ->where('movement_type', 'restock')
            ->get()
            ->sum(fn ($m) => $m->quantity * ($m->item->cost_price ?? 0));
    }

    public function currentStockValue(): float
    {
        return StockMovement::with('item')
            ->where('tenant_id', $this->tenantId)
            ->get()
            ->groupBy('item_id')
            ->sum(function ($movements) {
                $restock = $movements->where('movement_type', 'restock')->sum('quantity');
                $sold    = $movements->where('movement_type', 'sale')->sum('quantity');

                $remaining = $restock - $sold;
                $cost = $movements->first()->item->cost_price ?? 0;

                return max($remaining, 0) * $cost;
            });
    }

    /* =========================
     | VARIANCE (VALUE)
     * ========================= */

    public function varianceValue(\DateTimeInterface|string|null $date = null): float
    {
        $query = StockMovement::with('item')
            ->where('tenant_id', $this->tenantId);

        if ($date) {
            $query->whereDate('movement_date', $date);
        }

        return $query->get()
            ->groupBy('item_id')
            ->sum(function ($movements) {
                $opening = $movements->where('movement_type', 'opening_stock')->sum('quantity');
                $restock = $movements->where('movement_type', 'restock')->sum('quantity');
                $sold    = $movements->where('movement_type', 'sale')->sum('quantity');
                $closing = $movements->where('movement_type', 'closing_stock')->sum('quantity');

                $expected = $opening + $restock - $sold;
                $varianceQty = $expected - $closing;

                $cost = $movements->first()->item->cost_price ?? 0;

                return $varianceQty * $cost;
            });
    }

    public function hasVariance(\DateTimeInterface|string|null $date = null): bool
    {
        return $this->varianceValue($date) != 0;
    }

    /* =========================
     | COUNTER ANALYTICS
     * ========================= */

    public function salesValueByCounter(): Collection
    {
        return StockMovement::with(['item', 'counter'])
            ->where('tenant_id', $this->tenantId)
            ->where('movement_type', 'sale')
            ->whereNotNull('counter_id')
            ->get()
            ->groupBy('counter_id')
            ->map(fn ($m) => [
                'counter' => $m->first()->counter->name ?? 'Unknown',
                'value'   => $m->sum(fn ($x) => $x->quantity * ($x->item->selling_price ?? 0)),
            ]);
    }

    public function stockValueByCounter(): Collection
    {
        return StockMovement::with(['item', 'counter'])
            ->where('tenant_id', $this->tenantId)
            ->whereNotNull('counter_id')
            ->get()
            ->groupBy('counter_id')
            ->map(function ($movements) {
                $restock = $movements->where('movement_type', 'restock')->sum('quantity');
                $sold    = $movements->where('movement_type', 'sale')->sum('quantity');

                $remaining = $restock - $sold;
                $cost = $movements->first()->item->cost_price ?? 0;

                return [
                    'counter' => $movements->first()->counter->name ?? 'Unknown',
                    'value'   => max($remaining, 0) * $cost,
                ];
            });
    }
}
