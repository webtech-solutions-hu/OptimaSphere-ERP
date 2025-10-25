<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'unit', 'primarySupplier']);

        // Filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%")
                    ->orWhere('sku', 'like', "%{$request->search}%")
                    ->orWhere('barcode', 'like', "%{$request->search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:products,slug|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'type' => 'required|in:physical,service,digital',
            'base_price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'current_stock' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|unique:products,sku',
            'barcode' => 'nullable|string|unique:products,barcode',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::create($request->all());
        $product->load(['category', 'unit', 'primarySupplier']);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product,
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'unit', 'primarySupplier']);

        return response()->json([
            'data' => $product,
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:products,slug,' . $product->id . '|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'unit_id' => 'sometimes|exists:units,id',
            'type' => 'sometimes|in:physical,service,digital',
            'base_price' => 'sometimes|numeric|min:0',
            'cost_price' => 'sometimes|numeric|min:0',
            'current_stock' => 'nullable|numeric|min:0',
            'sku' => 'sometimes|string|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product->update($request->all());
        $product->load(['category', 'unit', 'primarySupplier']);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product,
        ]);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Bulk create products.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.name' => 'required|string|max:255',
            'products.*.slug' => 'required|string|unique:products,slug|max:255',
            'products.*.unit_id' => 'required|exists:units,id',
            'products.*.base_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $created = [];
        foreach ($request->products as $productData) {
            $created[] = Product::create($productData);
        }

        return response()->json([
            'message' => count($created) . ' products created successfully',
            'data' => $created,
        ], 201);
    }

    /**
     * Import products from CSV/JSON.
     */
    public function import(Request $request): JsonResponse
    {
        // This would typically handle file upload and parsing
        // For now, returning a placeholder response
        return response()->json([
            'message' => 'Import functionality - to be implemented with file processing',
        ], 501);
    }

    /**
     * Export products to CSV/JSON.
     */
    public function export(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'unit']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->get();

        return response()->json([
            'message' => 'Products export',
            'data' => $products,
            'count' => $products->count(),
        ]);
    }
}
