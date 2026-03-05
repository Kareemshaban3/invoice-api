<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
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

        return $q->latest('id')->paginate($perPage);
    }

    public function store(StoreClientRequest $request)
    {
        $client = Client::create($request->validated());

        return response()->json([
            'message' => __('messages.created'),
            'data' => $client,
        ], 201);
    }

    public function show(Client $client)
    {
        return response()->json(['data' => $client], 200);
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $client->update($request->validated());

        return response()->json([
            'message' => __('messages.updated'),
            'data' => $client
        ]);
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return response()->json(['message' => __('messages.deleted')]);
    }
}
