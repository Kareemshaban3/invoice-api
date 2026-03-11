<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitsRequest;
use App\Http\Requests\UpdateUnitsRequest;
use App\Models\Units;
use Illuminate\Http\Request;

class UnitsController extends Controller
{
    public function index()
    {
        $units = Units::paginate(10);
        return response()->json($units);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUnitsRequest $request)
    {
        $unit = Units::create($request->validated());
        return response()->json([
            'message' => 'Unit created successfully',
            'data' => $unit
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Units $unit)
    {
        return response()->json($unit);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUnitsRequest $request, Units $unit)
    {
        $unit->update($request->validated());
        return response()->json([
            'message' => 'Unit updated successfully',
            'data' => $unit
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Units $unit)
    {
        $unit->delete();
        return response()->json([
            'message' => 'Unit deleted successfully'
        ]);
    }
}
