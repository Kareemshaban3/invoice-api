<?php

namespace App\Http\Controllers;

use App\Models\Branche;
use App\Http\Requests\StoreBrancheRequest;
use App\Http\Requests\UpdateBrancheRequest;
use Illuminate\Http\Request;

class BrancheController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branches = Branche::paginate(10);

        return response()->json([
            'data' => $branches
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBrancheRequest $request)
    {
        $branch = Branche::create($request->validated());

        return response()->json([
            'message' => 'Branch created successfully',
            'data' => $branch
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Branche $branch)
    {
        return response()->json([
            'data' => $branch
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBrancheRequest $request, Branche $branch)
    {
        $branch->update($request->validated());

        return response()->json([
            'message' => 'Branch updated successfully',
            'data' => $branch
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branche $branch)
    {
        $branch->delete();

        return response()->json([
            'message' => 'Branch deleted successfully'
        ], 200);
    }
}