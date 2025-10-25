<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::with(['procurementOfficer', 'primaryCategory', 'productCategories']);

        // Filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_approved')) {
            $query->where('is_approved', $request->boolean('is_approved'));
        }

        if ($request->has('primary_category_id')) {
            $query->where('primary_category_id', $request->primary_category_id);
        }

        if ($request->has('min_rating')) {
            $query->where('performance_rating', '>=', $request->min_rating);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('company_name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $suppliers = $query->paginate($perPage);

        return response()->json($suppliers);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:manufacturer,distributor,service',
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|unique:suppliers,email',
            'phone' => 'nullable|string',
            'tax_id' => 'nullable|string|unique:suppliers,tax_id',
            'payment_terms' => 'nullable|integer|min:0',
            'primary_category_id' => 'nullable|exists:categories,id',
            'performance_rating' => 'nullable|numeric|min:0|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $supplier = Supplier::create($request->all());

        // Sync product categories if provided
        if ($request->has('product_category_ids')) {
            $supplier->productCategories()->sync($request->product_category_ids);
        }

        $supplier->load(['procurementOfficer', 'primaryCategory', 'productCategories']);

        return response()->json([
            'message' => 'Supplier created successfully',
            'data' => $supplier,
        ], 201);
    }

    /**
     * Display the specified supplier.
     */
    public function show(Supplier $supplier): JsonResponse
    {
        $supplier->load(['procurementOfficer', 'primaryCategory', 'productCategories', 'approver']);

        return response()->json([
            'data' => $supplier,
        ]);
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|email|unique:suppliers,email,' . $supplier->id,
            'phone' => 'nullable|string',
            'tax_id' => 'nullable|string|unique:suppliers,tax_id,' . $supplier->id,
            'payment_terms' => 'nullable|integer|min:0',
            'primary_category_id' => 'nullable|exists:categories,id',
            'performance_rating' => 'nullable|numeric|min:0|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $supplier->update($request->all());

        // Sync product categories if provided
        if ($request->has('product_category_ids')) {
            $supplier->productCategories()->sync($request->product_category_ids);
        }

        $supplier->load(['procurementOfficer', 'primaryCategory', 'productCategories']);

        return response()->json([
            'message' => 'Supplier updated successfully',
            'data' => $supplier,
        ]);
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return response()->json([
            'message' => 'Supplier deleted successfully',
        ]);
    }

    /**
     * Approve a supplier.
     */
    public function approve(Request $request, Supplier $supplier): JsonResponse
    {
        if ($supplier->is_approved) {
            return response()->json([
                'message' => 'Supplier is already approved',
            ], 400);
        }

        $supplier->update([
            'is_approved' => true,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Supplier approved successfully',
            'data' => $supplier,
        ]);
    }

    /**
     * Import suppliers from file.
     */
    public function import(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Import functionality - to be implemented with file processing',
        ], 501);
    }

    /**
     * Export suppliers.
     */
    public function export(Request $request): JsonResponse
    {
        $query = Supplier::with(['procurementOfficer', 'primaryCategory', 'productCategories']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $suppliers = $query->get();

        return response()->json([
            'message' => 'Suppliers export',
            'data' => $suppliers,
            'count' => $suppliers->count(),
        ]);
    }
}
