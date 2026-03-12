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
        $q = Product::query()->with([
            'prices.currency',
            'category',
            'supplier',
            'unit'
        ]);

        $perPage = (int) $request->attributes->get('per_page', 10);

        if ($s = $request->query('search')) {
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%")
                    ->orWhere('barcode', 'like', "%{$s}%");
            });
        }

        if ($request->boolean('in_stock')) {
            $q->where('stock', '>', 0);
        }

        if ($request->boolean('low_stock')) {
            $q->where('reorder_level', '>', 0)
                ->whereColumn('stock', '<=', 'reorder_level');
        }

        return $q->latest()->paginate($perPage);
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        $product = DB::transaction(function () use ($request, $data) {

            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')
                    ->store('products', 'public');
            }

            if (empty($data['sku'])) {
                $data['sku'] = Product::generateSku();
            }

            $prices = $data['prices'] ?? [];
            unset($data['prices']);

            $product = Product::create($data);

            foreach ($prices as $row) {
                ProductPrice::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'currency_id' => $row['currency_id'],
                    ],
                    ['price' => $row['price']]
                );
            }

            return $product->load([
                'prices.currency',
                'category',
                'supplier',
                'unit'
            ]);
        });

        return response()->json([
            'message' => __('messages.created'),
            'data' => $product
        ], 201);
    }


    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        $product = DB::transaction(function () use ($request, $data, $product) {

        
            if ($request->hasFile('image')) {

                if ($product->image_path) {
                    Storage::disk('public')->delete($product->image_path);
                }

                $path = $request->file('image')->store('products', 'public');

                $data['image_path'] = $path;
            }

         
            $prices = $data['prices'] ?? null;
            unset($data['prices']);

            $product->update($data);

            if (is_array($prices)) {
                foreach ($prices as $row) {

                    ProductPrice::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'currency_id' => $row['currency_id'],
                        ],
                        [
                            'price' => $row['price']
                        ]
                    );
                }
            }

            return $product->load([
                'prices.currency',
                'category',
                'supplier',
                'unit'
            ]);
        });

        return response()->json([
            'message' => __('messages.updated'),
            'data' => $product
        ]);
    }


    public function destroy(Product $product)
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return response()->json([
            'message' => __('messages.deleted')
        ]);
    }
}