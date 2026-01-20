<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
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
                $closingMovements = $movements->where('movement_type', 'closing_stock');
                $closingCount = $closingMovements->count();
                $closing = $closingMovements->sum('quantity');

                $expected = $opening + $restock - $sold;
                $variance = $closingCount === 0 ? null : $closing - $expected;

                $cost    = floatval($item->cost_price ?? 0);
                $selling = floatval($item->selling_price ?? 0);
                $profit  = $sold * ($selling - $cost);

                $totalOpening += $opening;
                $totalRestock += $restock;
                $totalSold    += $sold;
                $totalClosing += $closing;
                $totalExpected += $expected;
                if ($variance !== null) {
                    $totalVariance += $variance;
                }
                $totalProfit   += $profit;

                fputcsv($file, [
                    $item->item_name,
                    $opening,
                    $restock,
                    $sold,
                    $closing,
                    $expected,
                    $variance ?? '',
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

    private function buildTotals(Collection $grouped): array
    {
        $totals = [
            'opening' => 0,
            'restock' => 0,
            'sold' => 0,
            'closing' => 0,
            'expected' => 0,
            'variance' => 0,
            'profit' => 0,
            'cost_value' => 0,
            'selling_value' => 0,
        ];
        $varianceAvailable = false;
        $counterVarianceTotals = [];
        $counterVarianceHasData = [];

        foreach ($this->counters as $counterId => $counterName) {
            $counterVarianceTotals[$counterId] = 0;
            $counterVarianceHasData[$counterId] = false;
        }

        foreach ($grouped as $movements) {
            $item = $movements->first();
            $opening = $movements->where('movement_type', 'opening_stock')->sum('quantity');
            $restock = $movements->where('movement_type', 'restock')->sum('quantity');
            $sold    = $movements->where('movement_type', 'sale')->sum('quantity');
            $closingMovements = $movements->where('movement_type', 'closing_stock');
            $closingCount = $closingMovements->count();
            $closing = $closingMovements->sum('quantity');

            $expected = $opening + $restock - $sold;
            $variance = $closingCount === 0 ? $expected : $closing - $expected;

            $cost    = floatval($item->cost_price ?? 0);
            $selling = floatval($item->selling_price ?? 0);
            $profit  = $sold * ($selling - $cost);
            $costValue = $cost;
            $sellingValue = $selling;

            $totals['opening'] += $opening;
            $totals['restock'] += $restock;
            $totals['sold'] += $sold;
            $totals['closing'] += $closing;
            $totals['expected'] += $expected;
            if ($variance !== null) {
                $totals['variance'] += $variance;
                $varianceAvailable = true;
            }
            $totals['profit'] += $profit;
            $totals['cost_value'] += $costValue;
            $totals['selling_value'] += $sellingValue;

            $counterVariances = $this->calculateCounterVariances($movements);
            foreach ($counterVariances as $counterId => $cv) {
                if ($cv === null) {
                    continue;
                }
                $counterVarianceTotals[$counterId] += $cv;
                $counterVarianceHasData[$counterId] = true;
            }
        }

        return [
            'totals' => $totals,
            'varianceAvailable' => $varianceAvailable,
            'counterVarianceTotals' => $counterVarianceTotals,
            'counterVarianceHasData' => $counterVarianceHasData,
        ];
    }

   public function calculateCounterVariances($movements): array
{
    $variances = [];

    foreach ($this->counters as $counterId => $counterName) {
        $counterMovements = $movements->where('counter_id', $counterId);

        $opening = $counterMovements->where('movement_type', 'opening_stock')->sum('quantity');
        $restock = $counterMovements->where('movement_type', 'restock')->sum('quantity');
        $sold    = $counterMovements->where('movement_type', 'sale')->sum('quantity');
        $closingMovements = $counterMovements->where('movement_type', 'closing_stock');
        $closingCount = $closingMovements->count();
        $closing = $closingMovements->sum('quantity');

        $expected = $opening + $restock - $sold;
        $variances[$counterId] = $closingCount === 0 ? $expected : $closing - $expected;
    }

    return $variances;
}


    public function render()
    {
        $totalsData = $this->buildTotals(
            $this->allRows()->groupBy('item_id')
        );

        return view('livewire.stock-report-table', [
            'rows' => $this->rows, // paginated
            'items' => \App\Models\Item::orderBy('name')->get(),
            'counters' => $this->counters,
            'totals' => $totalsData['totals'],
            'varianceAvailable' => $totalsData['varianceAvailable'],
            'counterVarianceTotals' => $totalsData['counterVarianceTotals'],
            'counterVarianceHasData' => $totalsData['counterVarianceHasData'],

        ]);
    }
}
