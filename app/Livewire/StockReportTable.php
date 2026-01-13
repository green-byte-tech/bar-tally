<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Counter;

class StockReportTable extends Component
{
    use WithPagination;

    public $date;
    public $item;
    public array $counters = [];


    protected $paginationTheme = 'tailwind';

    public function updatedDate()
    {
        $this->resetPage();
    }

    public function updatedItem()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->date = today()->toDateString();
        $this->item = null;
        $this->counters = Counter::orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getRowsProperty()
    {
        $query = DB::table('stock_movements as sm')
            ->join('items as i', 'i.id', '=', 'sm.item_id')
            ->join('counters as c', 'c.id', '=', 'sm.counter_id')
            ->select(
                'sm.*',
                'i.name as item_name',
                'i.selling_price',
                'i.cost_price',
                'c.name as counter_name'
            )
            ->where('sm.movement_date', $this->date);

        if ($this->item) {
            $query->where('sm.item_id', $this->item);
        }

        return $query->orderBy('sm.item_id')->paginate(20);
    }


    // Unpaginated version for CSV
    public function allRows()
    {
        $query = DB::table('stock_movements as sm')
            ->join('items as i', 'i.id', '=', 'sm.item_id')
            ->join('counters as c', 'c.id', '=', 'sm.counter_id')
            ->select(
                'sm.*',
                'i.name as item_name',
                'i.selling_price',
                'i.cost_price',

            )
            ->where('sm.movement_date', $this->date);

        if ($this->item) {
            $query->where('sm.item_id', $this->item);
        }

        return $query->orderBy('sm.item_id')->get();
    }
    public array $counterVarianceTotals = [];

    public function resetCounterVarianceTotals(): void
    {
        $this->counterVarianceTotals = [];

        foreach ($this->counters as $counterId => $name) {
            $this->counterVarianceTotals[$counterId] = 0;
        }
    }

    public function exportCsv()
    {
        $filename = 'stock_report_' . now()->format('Y-m-d_H-i') . '.csv';

        $header = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        // EXPORT FULL DATASET, NOT PAGINATED
        $rows = $this->allRows()->groupBy('item_id');

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Item',
                'Opening',
                'Restock',
                'Sold',
                'Closing',
                'Expected',
                'Variance',
                'Cost',
                'Selling',
                'Profit'
            ]);

            $totalOpening = 0;
            $totalRestock = 0;
            $totalSold = 0;
            $totalClosing = 0;
            $totalExpected = 0;
            $totalVariance = 0;
            $totalProfit = 0;

            foreach ($rows as $itemId => $movements) {

                $item = $movements->first();

                $opening = $movements->where('movement_type', 'opening_stock')->sum('quantity');
                $restock = $movements->where('movement_type', 'restock')->sum('quantity');
                $sold    = $movements->where('movement_type', 'sale')->sum('quantity');
                $closing = $movements->where('movement_type', 'closing_stock')->sum('quantity');

                $expected = $opening + $restock - $sold;
                $variance = $expected - $closing;

                $cost    = floatval($item->cost_price ?? 0);
                $selling = floatval($item->selling_price ?? 0);
                $profit  = $sold * ($selling - $cost);

                $totalOpening += $opening;
                $totalRestock += $restock;
                $totalSold    += $sold;
                $totalClosing += $closing;
                $totalExpected += $expected;
                $totalVariance += $variance;
                $totalProfit   += $profit;

                fputcsv($file, [
                    $item->item_name,
                    $opening,
                    $restock,
                    $sold,
                    $closing,
                    $expected,
                    $variance,
                    $cost,
                    $selling,
                    $profit,
                ]);
            }

            fputcsv($file, [
                'TOTALS',
                $totalOpening,
                $totalRestock,
                $totalSold,
                $totalClosing,
                $totalExpected,
                $totalVariance,
                '',
                '',
                $totalProfit,
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $header);
    }
   public function calculateCounterVariances($movements): array
{
    $variances = [];

    foreach ($this->counters as $counterId => $counterName) {
        $counterMovements = $movements->where('counter_id', $counterId);

        $opening = $counterMovements->where('movement_type', 'opening_stock')->sum('quantity');
        $restock = $counterMovements->where('movement_type', 'restock')->sum('quantity');
        $sold    = $counterMovements->where('movement_type', 'sale')->sum('quantity');
        $closing = $counterMovements->where('movement_type', 'closing_stock')->sum('quantity');

        $expected = $opening + $restock - $sold;

        // 🔴 FIX: invert variance definition
        $variances[$counterId] = $closing - $expected;
    }

    return $variances;
}


    public function render()
    {
        $this->resetCounterVarianceTotals();

        return view('livewire.stock-report-table', [
            'rows' => $this->rows, // paginated
            'items' => \App\Models\Item::orderBy('name')->get(),
            'counters' => $this->counters,

        ]);
    }
}
