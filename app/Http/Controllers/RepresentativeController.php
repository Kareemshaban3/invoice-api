<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRepresentativeRequest;
use App\Http\Requests\UpdateRepresentativeRequest;
use App\Models\Representative;
use Illuminate\Http\Request;

class RepresentativeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = Representative::query();

        $perPage = (int) $request->input('per_page', 10);

        if ($s = $request->query('search')) {
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%$s%")
                    ->orWhere('phone', 'like', "%$s%")
                    ->orWhere('email', 'like', "%$s%");
            });
        }

        $representatives = $q->latest('id')->paginate($perPage);

        return response()->json([
            'data' => $representatives
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRepresentativeRequest $request)
    {
        $representative = Representative::create($request->validated());

        return response()->json([
            'message' => __('messages.created'),
            'data'    => $representative,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Representative $representative)
    {
        return response()->json(['data' => $representative], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRepresentativeRequest $request, Representative $representative)
    {
        $representative->update($request->validated());

        return response()->json([
            'message' => __('messages.updated'),
            'data'    => $representative,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Representative $representative)
    {
        $representative->delete();

        return response()->json(['message' => __('messages.deleted')], 200);
    }
}
