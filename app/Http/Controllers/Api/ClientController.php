<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = Client::query();
        $perPage = (int) $request->attributes->get('per_page', 10);

        if ($s = $request->query('search')) {
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%$s%")
                    ->orWhere('phone', 'like', "%$s%")
                    ->orWhere('email', 'like', "%$s%");
            });
        }

        return $q->latest()->paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest  $request)
    {
        $clinet = Client::create($request->validated());
        return response()->json([
            'message' => __('messages.created'),
            'data' => $clinet,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $clinet = Client::findOrFail($id);
        return response()->json([
            'message' => __('messages.created'),
            'data' => $clinet,
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        $client->update($request->validated());
        return response()->json(['message' => __('messages.updated'), 'data' => $client]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json(['message' => __('messages.deleted')]);
    }
}
