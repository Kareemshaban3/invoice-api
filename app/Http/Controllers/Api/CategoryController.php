<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = Category::query();
        $perPage = $request->attributes->get('per_page', 10);

        if ($s = $request->query('search')) {
            $q->where('name', 'like', "%{$s}%");
        }

        return $q->latest('id')->paginate($perPage);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
        ]);

        $cat = Category::create($data);
        return response()->json(['message' => __('messages.created'), 'data' => $cat], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return response()->json(['data' => $category]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', "unique:categories,name,{$category->id}"],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);

        $category->update($data);
        return response()->json(['message' => __('messages.updated'), 'data' => $category]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(['message' => __('messages.deleted')]);
    }
}
