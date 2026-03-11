<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCurrencyRequest;
use App\Http\Requests\UpdateCurrencyRequest;
use App\Models\Currency;

class CurrencyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $currencies = Currency::paginate(10);

        return response()->json([
            'data' => $currencies
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCurrencyRequest $request)
    {
        $currency = Currency::create($request->validated());

        return response()->json([
            'message' => 'Currency created successfully',
            'data' => $currency
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Currency $currency)
    {
        return response()->json([
            'data' => $currency
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCurrencyRequest $request, Currency $currency)
    {
        $currency->update($request->validated());

        return response()->json([
            'message' => 'Currency updated successfully',
            'data' => $currency
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Currency $currency)
    {
        $currency->delete();

        return response()->json([
            'message' => 'Currency deleted successfully'
        ], 200);
    }
}
