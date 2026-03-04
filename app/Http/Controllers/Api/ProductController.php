<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = Product::query()->with(['prices', 'category']);
        $perPage = (int) $request->attributes->get('per_page', 10);

        if ($s = $request->query('search')) {
            $q->where('name', 'like', "%{$s}%");
        }

        if ($currency = $request->query('currency')) {
            $currency = strtoupper((string) $currency);

            $q->whereHas('prices', fn($price) => $price->where('currency', $currency))
                ->with(['prices' => fn($price) => $price->where('currency', $currency)]);
        }

        if ($categoryId = $request->query('category_id')) {
            $q->where('category_id', $categoryId);
        }

        if ($request->boolean('in_stock')) {
            $q->where('stock', '>', 0);
        }

        return $q->latest('id')->paginate($perPage);
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        $product = DB::transaction(function () use ($data) {
            $product = Product::create([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'stock' => (int) $data['stock'],
            ]);

            $prices = collect($data['prices'])
                ->map(fn($row) => [
                    'currency' => $row['currency'],
                    'price' => $row['price'],
                ])
                ->all();

            $product->prices()->createMany($prices);

            return $product->load(['prices', 'category']);
        });

        return response()->json(['message' => __('messages.created'), 'data' => $product], 201);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        $updated = DB::transaction(function () use ($data, $product) {
            $productData = collect($data)->only(['name', 'description', 'category_id', 'stock'])->all();

            if (!empty($productData)) {
                $product->update($productData);
            }

            if (array_key_exists('prices', $data)) {
                $prices = collect($data['prices'])
                    ->map(fn($row) => [
                        'currency' => $row['currency'],
                        'price' => $row['price'],
                    ])
                    ->all();

                $product->prices()->delete();
                $product->prices()->createMany($prices);
            }

            return $product->load(['prices', 'category']);
        });

        return response()->json(['message' => __('messages.updated'), 'data' => $updated]);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => __('messages.deleted')]);
    }
}
