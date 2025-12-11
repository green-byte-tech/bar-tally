<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use Illuminate\Http\Request;
use App\Livewire\StockReportTable;

// Public routes
Route::get('/', function () {
    return view('welcome');
});

Route::get('/variation-data', function (Request $request) {
    $component = new StockReportTable();

    return response()->json($component->getVariationChartData());
});



