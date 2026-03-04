<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSuppliersRequest;
use App\Http\Requests\UpdateSuppliersRequest;
use App\Models\Suppliers;
use Illuminate\Http\Request;

class SuppliersController extends Controller
{
    public function index(Request $request)
    {
        $q = Suppliers::query();
        $perPage = (int) $request->attributes->get('per_page', 10);

        if ($s = $request->query('search')) {
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            });
        }

        return response()->json([
            'data' => $q->latest('id')->paginate($perPage),
        ]);
    }

    public function store(StoreSuppliersRequest $request)
    {
        $supplier = Suppliers::create($request->validated());

        return response()->json([
            'message' => __('messages.created'),
            'data' => $supplier,
        ], 201);
    }

    public function show(Suppliers $supplier)
    {
        return response()->json([
            'data' => $supplier,
        ]);
    }

    public function update(UpdateSuppliersRequest $request, Suppliers $supplier)
    {
        $supplier->update($request->validated());

        return response()->json([
            'message' => __('messages.updated'),
            'data' => $supplier,
        ]);
    }

    public function destroy(Suppliers $supplier)
    {
        $supplier->delete();

        return response()->json([
            'message' => __('messages.deleted'),
        ]);
    }
}
