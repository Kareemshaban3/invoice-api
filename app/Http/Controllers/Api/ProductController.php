<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = Product::query()->with(['prices.currency', 'category', 'supplier']);

        $perPage = (int) $request->attributes->get('per_page', 10);

        if ($s = $request->query('search')) {
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%")
                    ->orWhere('barcode', 'like', "%{$s}%");
            });
        }

        if ($currency = $request->query('currency')) {
            $currency = strtoupper((string) $currency);

            $q->whereHas('prices.currency', fn($q) => $q->where('code', $currency))
              ->with(['prices' => fn($price) => $price->whereHas('currency', fn($q) => $q->where('code', $currency))]);
        }

        if ($categoryId = $request->query('category_id')) {
            $q->where('category_id', $categoryId);
        }

        if ($supplierId = $request->query('supplier_id')) {
            $q->where('supplier_id', $supplierId);
        }

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        if ($request->boolean('in_stock')) {
            $q->where('stock', '>', 0);
        }

        if ($request->boolean('low_stock')) {
            $q->where('reorder_level', '>', 0)
                ->whereColumn('stock', '<=', 'reorder_level');
        }

        return $q->latest('id')->paginate($perPage);
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        $product = DB::transaction(function () use ($request, $data) {
            // image upload
            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('products', 'public');
            }

            // SKU generate if empty
            if (empty($data['sku'])) {
                $data['sku'] = Product::generateSku();
            }

            $prices = $data['prices'] ?? [];
            unset($data['prices']);

            /** @var Product $product */
            $product = Product::create($data);

            if (!empty($prices)) {
                foreach ($prices as $row) {
                    ProductPrice::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'currency_id' => $row['currency_id'], // استخدم currency_id
                        ],
                        ['price' => $row['price']]
                    );
                }
            }

            return $product->load(['prices.currency', 'category', 'supplier']);
        });

        return response()->json(['message' => __('messages.created'), 'data' => $product], 201);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        $updated = DB::transaction(function () use ($request, $data, $product) {
            if ($request->hasFile('image')) {
                if ($product->image_path) {
                    Storage::disk('public')->delete($product->image_path);
                }
                $data['image_path'] = $request->file('image')->store('products', 'public');
            }

            $prices = $data['prices'] ?? null;
            unset($data['prices']);

            if (!empty($data)) {
                $product->update($data);
            }

            // لو prices اتبعتت: Upsert
            if (is_array($prices)) {
                foreach ($prices as $row) {
                    ProductPrice::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'currency_id' => $row['currency_id'], // استخدم currency_id
                        ],
                        ['price' => $row['price']]
                    );
                }
            }

            return $product->load(['prices.currency', 'category', 'supplier']);
        });

        return response()->json(['message' => __('messages.updated'), 'data' => $updated]);
    }

    public function destroy(Product $product)
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();
        return response()->json(['message' => __('messages.deleted')]);
    }
}